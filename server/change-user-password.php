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
 * This: server/change-user-password.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for updating a user account password for Tic-Tac-Toe 3D.
 */

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');

    include_once('./global.php');
    include_once('./user-validation.php');
	include_once('./user-authentication.php');
	
	$response = new stdClass;
	$response->success = false;
	$response->message = ERROR_MSG_GENERIC;

	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($conn) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		if (isset($post['userId']) && isset($post['passwordCurrent'])
			&& isset($post['passwordNew']))
		{
			$uid = $post['userId'];
			$pwCur = $post['passwordCurrent'];
			$pwNew = $post['passwordNew'];
			
			if ($pwNew != $pwCur) {
				$pwV = validatePassword($pwNew);
				
				if ($pwV->valid) {
					$stmt = $conn->prepare(
						'SELECT password_hash
						FROM ttt3d_users
						WHERE user_id = ?');
					$stmt->bind_param('s', $uid);
					$stmt->execute();
					$stmt->store_result();
					
					if ($stmt->num_rows > 0) {
						$stmt->bind_result($pwCurHash);
						$stmt->fetch();
						$stmt->close();
						
						if (password_verify($pwCur, $pwCurHash)) {
							$pwNewHash = password_hash($pwNew, PASSWORD_BCRYPT);
							
							$stmt = $conn->prepare(
								'UPDATE ttt3d_users
								SET password_hash = ? AND last_login = NOW()
								WHERE user_id = ?');
							$stmt->bind_param('ss', $pwNewHash, $uid);
							$stmt->execute();
							
							if ($stmt->affected_rows > 0) {
								$response->success = true;
								$response->message = '';
							}
							$stmt->close();
						}
						else {
							$response->message = 'Current password is incorrect.';
						}
					}
					else {
						$stmt->close();
						$response->message = 'Invalid user ID';
					}
				}
				else {
					$response->message = $pwV->message;
				}
			}
			else {
				$response->message = 'New password must be different from current password.';
			}
		}
		else {
			$response->message = 'Invalid request data';
		}
		$conn->close();
	}
	
	echo json_encode($response);
?>
