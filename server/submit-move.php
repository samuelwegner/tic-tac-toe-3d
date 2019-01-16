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
 * This: server/submit-move.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for submitting a player's move for an in-progress
 *          game in Tic-Tac-Toe 3D.
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
		
		if (isset($post['userId']) && isset($post['gameId']) && isset($post['moveCoords'])) {
			$uid = $post['userId'];
			$gid = $post['gameId'];
			$coords = $post['moveCoords'];
			
			if (isUserLoggedIn($conn, $uid) && setUserLastLogin($conn, $uid)) {
				$game = getGameState($conn, $gid, $uid);
				
				if ($game != null) {
					if (!$game->isComplete) {
						if ($game->isFull && $game->turn) {
							$pnum = $game->myPlayerNum;
							
							if ($game->turn->playerNum == $pnum) {
								if (playMove($conn, $gid, $pnum, $game->turn->turnNum, $game->grid, $coords)) {
									$status = getGameWinStatus($game->grid, $coords);
									
									if ($status != null && $status != 0) {
										completeGame($conn, $gid, $status);
										$game->isComplete = true;
										$game->isTied = ($status < 0) ? true : false;
										$game->winningPlayerNum = ($status > 0) ? $status : null;
										$game->turn = null;
									}
									else {
										$game->turn = getGameTurn($conn, $gid, count($game->players));
									}
									
									$response->success = true;
									$response->message = '';
									$response->game = $game;
								}
								else {
									$response->message = 'Invalid game move';
								}
							}
							else {
								$response->message = 'It is not your turn.';
							}
						}
						else {
							$response->message = 'The game has not started yet.';
						}
					}
					else {
						$response->message = 'The game is already finished.';
					}
				}
				else {
					$response->message = 'Failed to retrieve game state.';
				}
			}
			else {
				$response->message = 'You must be loggged in to play a game.';
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
