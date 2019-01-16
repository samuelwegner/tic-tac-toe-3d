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
 * This: server/game.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Library containing server-side game logic functions and constants
 *          for Tic-Tac-Toe 3D.
 */

	const PLAYER_COUNT_MAX = 2; // Maximum number of players per game
	const TURN_TIMEOUT_SEC = 60; // Game turn time limit (seconds)
	
	/* Game grid side length (e.g., 3 == 3x3x3)
	   NOTE: This should not be set greater than 6 unless database tables
	   are modified accordingly (to support larger turn numbers, etc.) */
	const GAME_GRID_LEN = 3;
	
	/**
	 * Find the current game in which a given user is a player, if any exists.
	 * @param $conn MySQLi database connection
	 * @param $uid User ID of requesting client
	 * @return Game ID of player's open game, or null if none exists
	 */
	function findPlayerCurrentGame($conn, $uid) {
		if (!($conn instanceof mysqli) || !is_string($uid)) return null;
		
		$stmt = $conn->prepare(
			'SELECT g.game_id
			FROM ttt3d_games AS g
			WHERE g.is_complete = 0
				AND EXISTS (
					SELECT gp.game_id
					FROM ttt3d_game_players AS gp
					WHERE g.game_id = gp.game_id
						AND gp.player_id = ?
				)
			ORDER BY g.game_id ASC
			LIMIT 1');
		$stmt->bind_param('s', $uid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($gid);
			$stmt->fetch();
			$stmt->close();
			
			return $gid;
		}

		return null;
	}
	
	/**
	 * Find a game that is waiting for players, if any exists.
	 * @param $conn MySQLi database connection
	 * @param $uid User ID of requesting client
	 * @return Game ID of open game, or null if no open game exists
	 */
	function findOpenGame($conn, $uid) {
		if (!($conn instanceof mysqli) || !is_string($uid)) return null;
		
		$stmt = $conn->prepare(
			'SELECT g.game_id
			FROM ttt3d_games AS g
			WHERE g.is_complete = 0 AND g.is_full = 0
				AND NOT EXISTS (
					SELECT gp.game_id
					FROM ttt3d_game_players AS gp
					WHERE g.game_id = gp.game_id
						AND gp.player_id = ?
				)
			ORDER BY g.game_id ASC
			LIMIT 1');
		$stmt->bind_param('s', $uid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($gid);
			$stmt->fetch();
			$stmt->close();
			
			return $gid;
		}
		
		return null;
	}
	
	/**
	 * Create a new game and add a given user as a player.
	 * @param $conn MySQLi database connection
	 * @param $uid User ID to be added
	 * @return Game details object, or null on failure
	 */
	function createGame($conn, $uid) {
		if (!($conn instanceof mysqli) || !is_string($uid)) return null;
		
		$game = null;
		$uname = null;

		$stmt = $conn->prepare(
			'SELECT user_name
			FROM ttt3d_users
			WHERE user_id = ?');
		$stmt->bind_param('s', $uid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($uname);
			$stmt->fetch();
			$stmt->close();
		}
		else {
			$stmt->close();
			return null;
		}
		
		if ($conn->query('INSERT INTO ttt3d_games (winning_player) VALUES (NULL)')) {
			$gid = $conn->insert_id;
			
			$stmt = $conn->prepare(
				'INSERT INTO ttt3d_game_players
				(game_id, player_num, player_id)
				VALUES (?, 1, ?)');
			$stmt->bind_param('is', $gid, $uid);
			$stmt->execute();
			
			if ($stmt->affected_rows > 0) {
				$game = new stdClass;
				$game->gameId = $gid;
				$game->isFull = false;
				$game->myPlayerNum = 1;
				$game->players = array();
				$game->players[0] = new stdClass;
				$game->players[0]->playerNum = 1;
				$game->players[0]->playerName = $uname;
			}
			$stmt->close();
		}
		
		return $game;
	}
	
	/**
	 * Add a given player to an existing game.
	 * @param $conn MySQLi database connection
	 * @param $gid Game ID
	 * @param $uid User ID to be added
	 * @return True if player was added to game successfully
	 */
	function addGamePlayer($conn, $gid, $uid) {
		if (!($conn instanceof mysqli) || !is_int($gid) || !is_string($uid)) return false;
		
		$stmt = $conn->prepare(
			'SELECT game_id
			FROM ttt3d_games
			WHERE game_id = ? AND is_full = 0 AND is_complete = 0');
		$stmt->bind_param('i', $gid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->close();
		}
		else {
			$stmt->close();
			return false;
		}
		
		$pnum = 0;
		
		$stmt = $conn->prepare(
			'SELECT MAX(player_num)
			FROM ttt3d_game_players
			WHERE game_id = ?
			GROUP BY game_id');
		$stmt->bind_param('i', $gid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($c1);
			$stmt->fetch();
			$pnum = $c1;
		}
		$stmt->close();
		
		++$pnum;
		if ($pnum == PLAYER_COUNT_MAX) {
			$stmt = $conn->prepare(
				'UPDATE ttt3d_games
				SET is_full = 1
				WHERE game_id = ?');
			$stmt->bind_param('i', $gid);
			$stmt->execute();
			
			if ($stmt->affected_rows > 0) {
				$stmt->close();
			}
			else {
				$stmt->close();
				return false;
			}
		}
		
		$stmt = $conn->prepare(
			'INSERT INTO ttt3d_game_players
			(game_id, player_num, player_id)
			VALUES (?, ?, ?)');
		$stmt->bind_param('iis', $gid, $pnum, $uid);
		$stmt->execute();
		
		$ret = ($stmt->affected_rows > 0) ? true : false;
		
		$stmt->close();
		
		return $ret;
	}
	
	/**
	 * Generate a 3D array representing the current grid of a given game.
	 * Note that grid cell values correspond to player number, and 0 represents
	 * an empty grid space.
	 * @param $conn MySQLi database connection
	 * @param $gid Game ID
	 * @return Game grid array, or null on failure
	 */
	function getGameGrid($conn, $gid) {
		if (!($conn instanceof mysqli) || !is_int($gid)) return null;
		
		$grid = array();
		for ($x = 0; $x < GAME_GRID_LEN; ++$x) {
			$grid[$x] = array();
			for ($y = 0; $y < GAME_GRID_LEN; ++$y) {
				$grid[$x][$y] = array();
				for ($z = 0; $z < GAME_GRID_LEN; ++$z) {
					$grid[$x][$y][$z] = 0;
				}
			}
		}
		
		$stmt = $conn->prepare(
			'SELECT player_num, coord_x, coord_y, coord_z
			FROM ttt3d_game_turns
			WHERE game_id = ? AND coord_x IS NOT NULL
				AND coord_y IS NOT NULL AND coord_z IS NOT NULL');
		$stmt->bind_param('i', $gid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($pnum, $ix, $iy, $iz);
			
			while ($stmt->fetch()) {
				$grid[$ix][$iy][$iz] = $pnum;
			}
		}
		$stmt->close();
		
		return $grid;
	}
	
	/**
	 * Get the current turn data for a given game.
	 * If the last turn has expired, a new turn will be started.
	 * @param $conn MySQLi database connection
	 * @param $gid Game ID
	 * @param $pcount Count of players in the game
	 * @return Turn details object, or null on failure
	 */
	function getGameTurn($conn, $gid, $pcount) {
		if (!($conn instanceof mysqli) || !is_int($gid)) return null;
		if (!is_int($pcount) || $pcount < 1) return null;
		
		$turn = new stdClass;
		$makeTurn = false;
		$pnext = 0;
		$tnext = 0;
		
		$stmt = $conn->prepare(
			'SELECT turn_num, player_num, UNIX_TIMESTAMP(turn_started),
				UNIX_TIMESTAMP(turn_ended)
			FROM ttt3d_game_turns
			WHERE game_id = ?
			ORDER BY turn_num DESC
			LIMIT 1');
		$stmt->bind_param('i', $gid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result( $tnum, $tpnum, $tstart, $tend);
			$stmt->fetch();
			$stmt->close();
			
			if ($tend > 0) {
				$makeTurn = true;
				$tnext = $tnum;
				$pnext = $tpnum;
				
				$stmt = $conn->prepare(
					'UPDATE ttt3d_game_turns
					SET turn_ended = NOW()
					WHERE game_id = ? AND turn_num = ?');
				$stmt->bind_param('ii', $gid, $tnum);
				$stmt->execute();
				$stmt->close();
			}
			else {
				$tdiff = time() - $tstart;
				
				if ($tdiff >= TURN_TIMEOUT_SEC) {
					$makeTurn = true;
					$tnext = $tnum;
					$pnext = $tpnum;
				}
				else {
					$turn->turnNum = $tnum;
					$turn->playerNum = $tpnum;
					$turn->timeStarted = $tstart;
					$turn->timeRemaining = TURN_TIMEOUT_SEC - $tdiff;
				}
			}
		}
		else {
			$makeTurn = true;
			$stmt->close();
		}
		
		if ($makeTurn) {
			++$tnext;
			++$pnext;
			
			if ($pnext > $pcount) $pnext = 1;
			
			$stmt = $conn->prepare(
				'INSERT INTO ttt3d_game_turns
				(game_id, turn_num, player_num)
				VALUES (?, ?, ?)');
			$stmt->bind_param('iii', $gid, $tnext, $pnext);
			$stmt->execute();
			
			if ($stmt->affected_rows > 0) {
				$turn->turnNum = $tnext;
				$turn->playerNum = $pnext;
				$turn->timeStarted = time();
				$turn->timeRemaining = TURN_TIMEOUT_SEC;
			}
			$stmt->close();
		}
		
		return $turn;
	}

	/**
	 * Get the current state of a given game.
	 * The given user must be a player in the game or the request will fail.
	 * @param $conn MySQLi database connection
	 * @param $gid Game ID
	 * @param $uid User ID of requesting client
	 * @return Game details object, or null on failure
	 */
	function getGameState($conn, $gid, $uid) {
		if (!($conn instanceof mysqli) || !is_int($gid) || !is_string($uid)) return null;
		
		$game = null;
		
		$stmt = $conn->prepare(
			'SELECT is_full, is_complete, is_tied, winning_player
			FROM ttt3d_games
			WHERE game_id = ?');
		$stmt->bind_param('i', $gid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($full, $comp, $tied, $winner);
			$stmt->fetch();
			$stmt->close();
			
			$game = new stdClass;
			$game->gameId = $gid;
			$game->isFull = ($full > 0) ? true : false;
			$game->isComplete = ($comp > 0) ? true : false;
			$game->isTied = ($tied > 0) ? true : false;
			$game->winningPlayerNum = null;
			$game->myPlayerNum = null;
			$game->players = array();
			$game->turn = null;
			$game->grid = null;
			
			$stmt = $conn->prepare(
				'SELECT player_num, player_id, user_name
				FROM ttt3d_game_players, ttt3d_users
				WHERE game_id = ? AND player_id = user_id
				ORDER BY player_num ASC');
			$stmt->bind_param('i', $gid);
			$stmt->execute();
			$stmt->store_result();
			
			if ($stmt->num_rows > 0) {
				$stmt->bind_result($pnum, $pid, $pname);
				
				$pcount = 0;
				while ($stmt->fetch()) {
					$game->players[$pcount] = new stdClass;
					$game->players[$pcount]->playerNum = $pnum;
					$game->players[$pcount]->playerName = $pname;
					
					if ($pid == $winner) {
						$game->winningPlayerNum = $pnum;
					}
					
					if ($pid == $uid) {
						$game->myPlayerNum = $pnum;
					}
					
					++$pcount;
				}
				$stmt->close();
				
				if ($game->myPlayerNum == null) return null;
				
				if ($pcount >= PLAYER_COUNT_MAX) {
					if (!$game->isComplete) {
						$game->turn = getGameTurn($conn, $gid, $pcount);
					}
					
					$game->grid = getGameGrid($conn, $gid);
				}
			}
			else {
				$stmt->close();
			}
		}
		else {
			$stmt->close();
		}
		
		return $game;
	}
	
	/**
	 * Check whether a set of grid coordinates are valid for a given game.
	 * Valid coordinates must be within the bounds of the game grid and the space
	 * must not already be taken by a previous move.
	 * @param $grid 3D array representing the game grid
	 * @param $coords Coordinates (X, Y, Z) of move to check
	 * @return True if move is valid
	 */
	function isValidMove(&$grid, &$coords) {
		if (!is_array($grid) || !is_array($coords) || count($coords) != 3) return false;
		
		$x = $coords[0];
		$y = $coords[1];
		$z = $coords[2];
		
		if (!is_int($x) || $x < 0 || $x >= GAME_GRID_LEN) return false;
		if (!is_int($y) || $y < 0 || $y >= GAME_GRID_LEN) return false;
		if (!is_int($z) || $z < 0 || $z >= GAME_GRID_LEN) return false;
		
		return $grid[$x][$y][$z] === 0;
	}
	
	/**
	 * Attempt to play a move for a given player in a given game.
	 * If the move is valid, the game grid and turn records will be updated.
	 * @param $conn MySQLi database connection
	 * @param $gid Game ID
	 * @param $pnum Player number
	 * @param $tnum Current turn number
	 * @param $grid 3D array representing the game grid
	 * @param $coords Coordinates (X, Y, Z) of move
	 * @return True if move was made successfully
	 */
	function playMove($conn, $gid, $pnum, $tnum, &$grid, &$coords) {
		if (!isValidMove($grid, $coords)) return false;
		if (!($conn instanceof mysqli) || !is_int($gid)) return false;
		if (!is_int($pnum) || $pnum < 1) return false;
		if (!is_int($tnum) || $tnum < 1) return false;
		
		$x = $coords[0];
		$y = $coords[1];
		$z = $coords[2];

		$stmt = $conn->prepare(
			'UPDATE ttt3d_game_turns
			SET turn_ended = NOW(),
				coord_x = ?,
				coord_y = ?,
				coord_z = ?
			WHERE game_id = ? AND turn_num = ?');
		$stmt->bind_param('iiiii', $x, $y, $z, $gid, $tnum);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0) {
			$stmt->close();
		}
		else {
			$stmt->close();
			return false;
		}
		
		$grid[$x][$y][$z] = $pnum;
		
		return true;
	}
	
	/**
	 * Calculate the win status of a game based on the game grid array.
	 * Grid MUST be a 3D array with each dimension of length GAME_GRID_LEN.
	 * @param $grid 3D array representing the game grid
	 * @param $coords Coordinates (X, Y, Z) of last move
	 * @return	N == 0	: Game is unfinished
	 *			N > 0	: Player number N wins
	 *			N < 0	: Game is tied
	 *			null	: Error
	 */
	function getGameWinStatus(&$grid, &$coords) {
		if (!is_array($grid) || !is_array($coords) || count($coords) != 3) return null;
		
		$x = $coords[0];
		$y = $coords[1];
		$z = $coords[2];

		if (!is_int($x) || $x < 0 || $x >= GAME_GRID_LEN) return null;
		if (!is_int($y) || $y < 0 || $y >= GAME_GRID_LEN) return null;
		if (!is_int($z) || $z < 0 || $z >= GAME_GRID_LEN) return null;
		
		$pnum = $grid[$x][$y][$z];
		if (!is_int($pnum) || $pnum <= 0) return null;
		
		// Axial: (0,y,z) to (n,y,z)
		$matched = 0;
		for ($ix = 0; $ix < GAME_GRID_LEN; ++$ix) {
			if ($pnum === $grid[$ix][$y][$z]) {
				++$matched;
			}
			else {
				break;
			}
		}
		if ($matched === GAME_GRID_LEN) return $pnum;
		
		// Axial: (x,0,z) to (x,n,z)
		$matched = 0;
		for ($iy = 0; $iy < GAME_GRID_LEN; ++$iy) {
			if ($pnum === $grid[$x][$iy][$z]) {
				++$matched;
			}
			else {
				break;
			}
		}
		if ($matched === GAME_GRID_LEN) return $pnum;

		// Axial: (x,y,0) to (x,y,n)
		$matched = 0;
		for ($iz = 0; $iz < GAME_GRID_LEN; ++$iz) {
			if ($pnum === $grid[$x][$y][$iz]) {
				++$matched;
			}
			else {
				break;
			}
		}
		if ($matched === GAME_GRID_LEN) return $pnum;
		
		// Diagonal: (x,0,0) to (x,n,n)
		if ($y === $z) {
			$matched = 0;
			for ($iy = 0, $iz = 0;
				$iy < GAME_GRID_LEN && $iz < GAME_GRID_LEN;
				++$iy, ++$iz)
			{
				if ($pnum === $grid[$x][$iy][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}
		
		// Diagonal: (x,n,0) to (x,0,n)
		if (($y + $z) === (GAME_GRID_LEN - 1)) {
			$matched = 0;
			for ($iy = (GAME_GRID_LEN - 1), $iz = 0;
				$iy >= 0 && $iz < GAME_GRID_LEN;
				--$iy, ++$iz)
			{
				if ($pnum === $grid[$x][$iy][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}
		
		// Diagonal: (0,y,0) to (n,y,n)
		if ($x === $z) {
			$matched = 0;
			for ($ix = 0, $iz = 0;
				$ix < GAME_GRID_LEN && $iz < GAME_GRID_LEN;
				++$ix, ++$iz)
			{
				if ($pnum === $grid[$ix][$y][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}

		// Diagonal: (n,y,0) to (0,y,n)
		if (($x + $z) === (GAME_GRID_LEN - 1)) {
			$matched = 0;
			for ($ix = (GAME_GRID_LEN - 1), $iz = 0;
				$ix >= 0 && $iz < GAME_GRID_LEN;
				--$ix, ++$iz)
			{
				if ($pnum === $grid[$ix][$y][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}

		// Diagonal: (0,0,z) to (n,n,z)
		if ($x === $y) {
			$matched = 0;
			for ($ix = 0, $iy = 0;
				$ix < GAME_GRID_LEN && $iy < GAME_GRID_LEN;
				++$ix, ++$iy)
			{
				if ($pnum === $grid[$ix][$iy][$z]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}

		// Diagonal: (n,0,z) to (0,n,z)
		if (($x + $y) === (GAME_GRID_LEN - 1)) {
			$matched = 0;
			for ($ix = (GAME_GRID_LEN - 1), $iy = 0;
				$ix >= 0 && $iy < GAME_GRID_LEN;
				--$ix, ++$iy)
			{
				if ($pnum === $grid[$ix][$iy][$z]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}

		// Diagonal: (0,0,0) to (n,n,n)
		if (($x === $y) && ($y === $z)) {
			$matched = 0;
			for ($ix = 0, $iy = 0, $iz = 0;
				$ix < GAME_GRID_LEN && $iy < GAME_GRID_LEN && $iz < GAME_GRID_LEN;
				++$ix, ++$iy, ++$iz)
			{
				if ($pnum === $grid[$ix][$iy][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}

		// Diagonal: (0,0,n) to (n,n,0)
		if (($x === $y) && (($y + $z) === (GAME_GRID_LEN - 1))) {
			$matched = 0;
			for ($ix = 0, $iy = 0, $iz = (GAME_GRID_LEN - 1);
				$ix < GAME_GRID_LEN && $iy < GAME_GRID_LEN && $iz >= 0;
				++$ix, ++$iy, --$iz)
			{
				if ($pnum === $grid[$ix][$iy][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}
		
		// Diagonal: (0,n,n) to (n,0,0)
		if (($y === $z) && (($z + $x) === (GAME_GRID_LEN - 1))) {
			$matched = 0;
			for ($ix = 0, $iy = (GAME_GRID_LEN - 1), $iz = (GAME_GRID_LEN - 1);
				$ix < GAME_GRID_LEN && $iy >= 0 && $iz >= 0;
				++$ix, --$iy, --$iz)
			{
				if ($pnum === $grid[$ix][$iy][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}

		// Diagonal: (n,0,n) to (0,n,0)
		if (($x === $z) && (($z + $y) === (GAME_GRID_LEN - 1))) {
			$matched = 0;
			for ($ix = (GAME_GRID_LEN - 1), $iy = 0, $iz = (GAME_GRID_LEN - 1);
				$ix >= 0 && $iy < GAME_GRID_LEN && $iz >= 0;
				--$ix, ++$iy, --$iz)
			{
				if ($pnum === $grid[$ix][$iy][$iz]) {
					++$matched;
				}
				else {
					break;
				}
			}
			if ($matched === GAME_GRID_LEN) return $pnum;
		}
		
		// Check for tie
		for ($ix = 0; $ix < GAME_GRID_LEN; ++$ix) {
			for ($iy = 0; $iy < GAME_GRID_LEN; ++$iy) {
				for ($iz = 0; $iz < GAME_GRID_LEN; ++$iz) {
					if (0 === $grid[$ix][$iy][$iz]) {
						return 0;
					}
				}
			}
		}
		return -1;
	}
	
	/**
	 * Mark a game as complete and set relevant game status fields.
	 * @param $conn MySQLi database connection
	 * @param $gid Game ID
	 * @param $status Completion status code:
	 *				N == 0	: Game ends without a winner or a tie
	 *				N > 0	: Player number N wins
	 *				N < 0	: Game ends in a tie
	 * @return True if game was completed successfully
	 */
	function completeGame($conn, $gid, $status) {
		if (!($conn instanceof mysqli) || !is_int($gid) || !is_int($status)) return false;
		
		$tied = ($status < 0) ? 1 : 0;
		$winner = null;
		
		if ($status > 0) {
			$stmt = $conn->prepare(
				'SELECT player_id
				FROM ttt3d_game_players
				WHERE game_id = ? AND player_num = ?');
			$stmt->bind_param('ii', $gid, $status);
			$stmt->execute();
			$stmt->store_result();
			
			if ($stmt->num_rows > 0) {
				$stmt->bind_result($winner);
				$stmt->fetch();
				$stmt->close();
			}
			else {
				$stmt->close();
				return false;
			}
			
			$stmt = $conn->prepare(
				'SELECT player_num, player_id
				FROM ttt3d_game_players
				WHERE game_id = ?');
			$stmt->bind_param('i', $gid);
			$stmt->execute();
			$stmt->store_result();
			
			if ($stmt->num_rows > 0) {
				$stmt->bind_result($pnum, $uid);
				while ($stmt->fetch()) {
					if ($pnum == $status) {
						updatePlayerWinStats($conn, $uid, 1, 0, 0);
					}
					else {
						updatePlayerWinStats($conn, $uid, 0, 1, 0);
					}
				}
			}
			$stmt->close();
		}
		else if ($status < 0) {
			$stmt = $conn->prepare(
				'SELECT player_num, player_id
				FROM ttt3d_game_players
				WHERE game_id = ?');
			$stmt->bind_param('i', $gid);
			$stmt->execute();
			$stmt->store_result();
			
			if ($stmt->num_rows > 0) {
				$stmt->bind_result($pnum, $uid);
				while ($stmt->fetch()) {
					updatePlayerWinStats($conn, $uid, 0, 0, 1);
				}
			}
			$stmt->close();
		}
		
		$stmt = $conn->prepare(
			'UPDATE ttt3d_games
			SET is_complete = 1,
				is_tied = ?,
				winning_player = ?
			WHERE game_id = ?');
		$stmt->bind_param('isi', $tied, $winner, $gid);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0) {
			$stmt->close();
			return true;
		}
		else {
			$stmt->close();
			return false;
		}
	}
	
	/**
	 * Modify a user's game history statistics.
	 * @param $conn MySQLi database connection
	 * @param $uid User ID
	 * @param $wins Number of wins to be added
	 * @param $losses Number of losses to be added
	 * @param $ties Number of ties to be added
	 * @return True if user record was updated successfully
	 */
	function updatePlayerWinStats($conn, $uid, $wins, $losses, $ties) {
		if (!($conn instanceof mysqli) || !is_string($uid)) return false;
		if (!is_int($wins) || !is_int($losses) || !is_int($ties)) return false;

		$stmt = $conn->prepare(
			'SELECT games_won, games_lost, games_tied
			FROM ttt3d_users
			WHERE user_id = ?');
		$stmt->bind_param('s', $uid);
		$stmt->execute();
		$stmt->store_result();
		
		if ($stmt->num_rows > 0) {
			$stmt->bind_result($c1, $c2, $c3);
			$stmt->fetch();
			$stmt->close();
			
			$wins += $c1;
			$losses += $c2;
			$ties += $c3;
			
			if ($wins < 0) $wins = 0;
			if ($losses < 0) $losses = 0;
			if ($ties < 0) $ties = 0;
		}
		else {
			$stmt->close();
			return false;
		}
		
		$stmt = $conn->prepare(
			'UPDATE ttt3d_users
			SET games_won = ?,
				games_lost = ?,
				games_tied = ?
			WHERE user_id = ?');
		$stmt->bind_param('iiis', $wins, $losses, $ties, $uid);
		$stmt->execute();
		
		$ret = ($stmt->affected_rows > 0) ? true : false;
		
		$stmt->close();
		
		return $ret;
	}
?>