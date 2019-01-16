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
 * This: server/get-user-games.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for retrieving a list of completed games for a
 *          logged-in user of Tic-Tac-Toe 3D.
 */

	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');

    include_once('./global.php');
	include_once('./user-authentication.php');
	
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
				$games = new stdClass;
				$games->won = array();
				$games->lost = array();
				$games->tied = array();
				
				// Games won
				$stmt = $conn->prepare(
					'SELECT game_id
					FROM ttt3d_games
					WHERE is_complete = 1 AND winning_player = ?
					ORDER BY game_id DESC');
				$stmt->bind_param('s', $uid);
				$stmt->execute();
				$stmt->store_result();
				
				if ($stmt->num_rows > 0) {
					$stmt->bind_result($gid);
					
					while ($stmt->fetch()) {
						$games->won[] = $gid;
					}
				}
				$stmt->close();
				
				// Games lost
				$stmt = $conn->prepare(
					'SELECT g.game_id
					FROM ttt3d_games AS g
					WHERE g.is_complete = 1
						AND g.winning_player IS NOT NULL
						AND g.winning_player <> ?
						AND EXISTS (
							SELECT *
							FROM ttt3d_game_players AS gp
							WHERE g.game_id = gp.game_id
								AND gp.player_id = ?
						)
					ORDER BY g.game_id DESC');
				$stmt->bind_param('ss', $uid, $uid);
				$stmt->execute();
				$stmt->store_result();
				
				if ($stmt->num_rows > 0) {
					$stmt->bind_result($gid);
					
					while ($stmt->fetch()) {
						$games->lost[] = $gid;
					}
				}
				$stmt->close();
				
				// Games tied
				$stmt = $conn->prepare(
					'SELECT g.game_id
					FROM ttt3d_games AS g
					WHERE g.is_complete = 1 AND g.is_tied = 1
						AND EXISTS (
							SELECT *
							FROM ttt3d_game_players AS gp
							WHERE g.game_id = gp.game_id
								AND gp.player_id = ?
						)
					ORDER BY g.game_id DESC');
				$stmt->bind_param('s', $uid);
				$stmt->execute();
				$stmt->store_result();
				
				if ($stmt->num_rows > 0) {
					$stmt->bind_result($gid);
					
					while ($stmt->fetch()) {
						$games->tied[] = $gid;
					}
				}
				$stmt->close();
				
				$response->success = true;
				$response->message = '';
				$response->games = $games;
			}
			else {
				$response->message = 'You must be logged in to retrieve game data.';
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
