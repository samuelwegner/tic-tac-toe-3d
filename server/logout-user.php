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
 * This: server/logout-user.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for ending a login session for a user in Tic-Tac-Toe 3D.
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
		
		if (isset($post['userId'])) {
			$uid = $post['userId'];
			
			if (!isUserLoggedIn($conn, $uid) || logOutUser($conn, $uid)) {
				$response->success = true;
				$response->message = '';
			}
			$conn->close();
		}
		else {
			$response->message = 'Invalid request data';
		}
	}
	
	echo json_encode($response);
?>
