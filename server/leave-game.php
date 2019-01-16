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
 * This: server/leave-game.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Web service for forfeiting an in-progress game of Tic-Tac-Toe 3D.
 *          Depending on the current game state, the player who chose to leave
 *          game may receive a loss for that game.
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
	$response->gameLost = false;

	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	if ($conn) {
		$post = json_decode(file_get_contents('php://input'), true);
		
		if (isset($post['userId']) && isset($post['gameId'])) {
			$uid = $post['userId'];
			$gid = $post['gameId'];
			
			if (isUserLoggedIn($conn, $uid) && setUserLastLogin($conn, $uid)) {
				$game = getGameState($conn, $gid, $uid);
				
				if ($game != null) {
					$comp = false;
					$penalty = false;
					
					if (!$game->isComplete) {
						if ($game->isFull && $game->grid != null) {
							for ($x = 0; $x < GAME_GRID_LEN; ++$x) {
								for ($y = 0; $y < GAME_GRID_LEN; ++$y) {
									for ($z = 0; $z < GAME_GRID_LEN; ++$z) {
										if ($game->grid[$x][$y][$z] !== 0) {
											$penalty = true;
											goto end_grid_search;
										}
									}
								}
							}
						}
						end_grid_search:
						
						if ($penalty) {
							$winner = null;
							$pcount = count($game->players);
							
							if (is_int($pcount) && $pcount <= 2 && $pcount > 0) {
								
								for ($i = 0; $i < $pcount; ++$i) {
									if ($game->players[$i]->playerNum != $game->myPlayerNum) {
										$winner = $game->players[$i]->playerNum;
										break;
									}
								}
							}
							
							if ($winner) {
								$comp = completeGame($conn, $gid, $winner);
							}
							else {
								$comp = completeGame($conn, $gid, 0);
								if ($comp) {
									updatePlayerWinStats($conn, $uid, 0, 1, 0);
								}
							}
						}
						else {
							$comp = completeGame($conn, $gid, 0);
						}
					}
					else {
						$comp = true;
					}
					
					if ($comp) {
						$response->success = true;
						$response->message = '';
						$response->gameLost = $penalty;
					}
				}
				else {
					$response->message = 'Failed to retrieve game state.';
				}
			}
			else {
				$response->message = 'You must log in before modifying a game.';
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
