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
 * This: server/register-user.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for creating a new user account in Tic-Tac-Toe 3D.
 */

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');

    include_once('./global.php');
    include_once('./user-validation.php');
	
	$response = new stdClass;
	$response->success = false;
	$response->message = ERROR_MSG_GENERIC;

	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($conn) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		if (isset($post['userId']) && isset($post['userName']) && isset($post['password'])) {
			$uid = $post['userId'];
			$name = $post['userName'];
			$pw = $post['password'];
			
			$uidV = validateUserId($uid);
			$nameV = validateUserName($name);
			$pwV = validatePassword($pw);
			
			if (!$uidV->valid) {
				$response->message = $uidV->message;
			}
			else if (!$nameV->valid) {
				$response->message = $nameV->message;
			}
			else if (!$pwV->valid) {
				$response->message = $pwV->message;
			}
			else {
				$pwHash = password_hash($pw, PASSWORD_BCRYPT);
				
				$stmt = $conn->prepare(
					'INSERT INTO ttt3d_users
					(user_id, user_name, password_hash, last_login)
					VALUES (?, ?, ?, NOW())');
				$stmt->bind_param('sss', $uid, $name, $pwHash);
				$stmt->execute();
				
				if ($stmt->affected_rows > 0) {
					$response->success = true;
					$response->message = '';
				}
				$stmt->close();
			}
			$conn->close();
		}
		else {
			$response->message = 'Invalid request data';
		}
	}
	
	echo json_encode($response);
?>
