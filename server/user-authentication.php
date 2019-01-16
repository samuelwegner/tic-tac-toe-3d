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
 * This: server/user-authentication.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Library containing server-side functions and constants to
 *          authenticate user login sessions for Tic-Tac-Toe 3D.
 */

	const LOGIN_TIMEOUT_SEC = 600; // User login timeout limit (seconds)

	/**
	 * Verify whether a given user is currently logged in.
	 * @param $conn MySQLi database connection
	 * @param $uid User ID
	 * @return True if user is logged in
	 */
	function isUserLoggedIn($conn, $uid) {
		if (!($conn instanceof mysqli) || !is_string($uid)) return false;
		
		$timeout = LOGIN_TIMEOUT_SEC;
		
		$stmt = $conn->prepare(
			'SELECT user_id
			FROM ttt3d_users
			WHERE user_id = ? AND ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(last_login)) < ?)');
		$stmt->bind_param('si', $uid, $timeout);
		$stmt->execute();
		$stmt->store_result();
		
		$ret = ($stmt->num_rows > 0) ? true : false;
		
		$stmt->close();
		
		return $ret;
	}
	
	/**
	 * Update a given user's last login timestamp to the current time.
	 * @param $conn MySQLi database connection
	 * @param $uid User ID
	 * @return True if login timestamp was set successfully
	 */
	function setUserLastLogin($conn, $uid) {
		if (!($conn instanceof mysqli) || !is_string($uid)) return false;
		
		$stmt = $conn->prepare(
			'UPDATE ttt3d_users
			SET last_login = NOW()
			WHERE user_id = ?');
		$stmt->bind_param('s', $uid);
		$stmt->execute();
		
		$ret = ($stmt->affected_rows > 0) ? true : false;
		
		$stmt->close();
		
		return $ret;
	}
	
	/**
	 * Mark a given user as logged out.
	 * @param $conn MySQLi database connection
	 * @param $uid User ID
	 * @return True if user was logged out successfully
	 */
	function logOutUser($conn, $uid) {
		if (!($conn instanceof mysqli) || !is_string($uid)) return false;
		
		$timeout = LOGIN_TIMEOUT_SEC;

		$stmt = $conn->prepare(
			'UPDATE ttt3d_users
			SET last_login = FROM_UNIXTIME(UNIX_TIMESTAMP() - ?)
			WHERE user_id = ?');
		$stmt->bind_param('is', $timeout, $uid);
		$stmt->execute();
		
		$ret = ($stmt->affected_rows > 0) ? true : false;
		
		$stmt->close();
		
		return $ret;
	}
?>