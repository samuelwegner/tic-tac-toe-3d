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
 * This: server/join-game.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for joining a matchmaking game in Tic-Tac-Toe 3D.
 *          If there is an open game, the specified user will join that game.
 *          If no open games exist, a new game will be created and the
 *          specified user will be added. If the user is already part of an
 *          open game, that game will be returned.
 */

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');

    include_once('./global.php');
	include_once('./user-authentication.php');
    include_once('./game.php');
	
	$response = new stdClass;
	$response->success = false;
	$response->message = ERROR_MSG_GENERIC;
	$response->loginRequired = false;

	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($conn) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		if (isset($post['userId'])) {
			$uid = $post['userId'];
			
			if (isUserLoggedIn($conn, $uid) && setUserLastLogin($conn, $uid)) {
				$game = null;
				
				$gid = findPlayerCurrentGame($conn, $uid);
				if ($gid != null) {
					$game = getGameState($conn, $gid, $uid);
				}
				else {
					$gid = findOpenGame($conn, $uid);
					if ($gid == null) {
						$game = createGame($conn, $uid);
					}
					else if (addGamePlayer($conn, $gid, $uid)) {
						$game = getGameState($conn, $gid, $uid);
					}
				}
				
				if ($game != null) {
					$response->success = true;
					$response->message = '';
					$response->game = $game;
				}
				else {
					$response->message = 'Failed to join a game.';
				}
			}
			else {
				$response->message = 'You must log in before joining a game.';
				$response->loginRequired = true;
			}
			$conn->close();
		}
		else {
			$response->message = 'Invalid request data';
		}
	}
	
	echo json_encode($response);
?>
