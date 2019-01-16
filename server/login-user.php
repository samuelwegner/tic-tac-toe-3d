<?php
/******************************************************************************
    Copyright 2018, 2019  Samuel Wegner (samuelwegner@hotmail.com)
	
	This file is part of Tic-Tac-Toe 3D.

    Tic-Tac-Toe 3D is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Tic-Tac-Toe 3D is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Tic-Tac-Toe 3D.  If not, see <https://www.gnu.org/licenses/>.
******************************************************************************/

/* Project: https://github.com/samuelwegner/tic-tac-toe-3d
 * This: server/login-user.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for authenticating a login session for a user
 *          in Tic-Tac-Toe 3D.
 */

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	
	include_once('./global.php');
	include_once('./user-authentication.php');
	
	$response = new stdClass;
	$response->success = false;
	$response->message = ERROR_MSG_GENERIC;

	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($conn) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		if (isset($post['userId']) && isset($post['password'])) {
			$uid = $post['userId'];
			$pw = $post['password'];
			
			$stmt = $conn->prepare(
				'SELECT user_id, user_name, password_hash, games_won, games_lost, games_tied
				FROM ttt3d_users
				WHERE user_id = ?');
			$stmt->bind_param('s', $uid);
			$stmt->execute();
			$stmt->store_result();
			
			if ($stmt->num_rows > 0) {
				$user = new stdClass;
				$stmt->bind_result(
					$user->userId,
					$user->userName,
					$pwHash,
					$user->gamesWon,
					$user->gamesLost,
					$user->gamesTied);
				$stmt->fetch();
				$stmt->close();
				
				if (password_verify($pw, $pwHash)) {
					if (setUserLastLogin($conn, $uid)) {
						$response->success = true;
						$response->message = '';
						$response->user = $user;
					}
				}
				else {
					$response->message = 'Invalid password';
				}
			}
			else {
				$response->message = 'Invalid user ID';
			}
			$conn->close();
		}
		else {
			$response->message = 'Invalid request data';
		}
	}
	
	echo json_encode($response);
?>
