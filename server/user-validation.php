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
 * This: server/user-validation.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Library containing server-side functions and constants to
 *          validate user account data for Tic-Tac-Toe 3D.
 *          If the "ttt3d_users" database table definition is modified,
 *          it may be necessary to modify related constants in this file
 *          to reflect those changes.
 */

	const USER_ID_LEN_MIN = 3; // User ID minimum character length
	const USER_ID_LEN_MAX = 24; // User ID maximum character length
	const USER_NAME_LEN_MIN = 1; // User display name minimum character length
	const USER_NAME_LEN_MAX = 50; // User display name maximum character length
	const PASSWORD_LEN_MIN = 8; // Password minimum character length
	const PASSWORD_LEN_MAX = 128; // Password maximum character length
	
	/**
	 * Verify that the user ID is valid for use.
	 * Currently, only the length of the ID is validated.
	 * @param $uid User ID to check
	 * @return True if ID is valid
	 */
	function validateUserId($uid) {
		$ret = new stdClass;
		$ret->valid = false;
		$ret->message = 'Invalid user ID';
		
		if (!is_string($uid)) {
			return $ret;
		}
		
		$len = strlen($uid);
		
		if ($len < USER_ID_LEN_MIN) {
			$ret->message = 'User ID must be at least ' . USER_ID_LEN_MIN . ' characters';
			return $ret;
		}
		
		if ($len > USER_ID_LEN_MAX) {
			$ret->message = 'User ID must be at most ' . USER_ID_LEN_MAX  . ' characters';
			return $ret;
		}

		$ret->valid = true;
		$ret->message = '';
		return $ret;
	}
	
	/**
	 * Verify that the user display name is valid for use.
	 * Currently, only the length of the name is validated.
	 * @param $name User name to check
	 * @return True if name is valid
	 */
	function validateUserName($name) {
		$ret = new stdClass;
		$ret->valid = false;
		$ret->message = 'Invalid user name';
		
		if (!is_string($name)) {
			return $ret;
		}
		
		$len = strlen($name);
		
		if ($len < USER_NAME_LEN_MIN) {
			$ret->message = 'User name must be at least ' . USER_NAME_LEN_MIN . ' characters';
			return $ret;
		}
		
		if ($len > USER_NAME_LEN_MAX) {
			$ret->message = 'User name must be at most ' . USER_NAME_LEN_MAX  . ' characters';
			return $ret;
		}

		$ret->valid = true;
		$ret->message = '';
		return $ret;
	}
	
	/**
	 * Verify that the password is valid for use.
	 * Currently, only the length of the password is validated.
	 * @param $uid Password to check
	 * @return True if password is valid
	 */
	function validatePassword($pw) {
		$ret = new stdClass;
		$ret->valid = false;
		$ret->message = 'Invalid password';
		
		if (!is_string($pw)) {
			return $ret;
		}
		
		$len = strlen($pw);
		
		if ($len < PASSWORD_LEN_MIN) {
			$ret->message = 'Password must be at least ' . PASSWORD_LEN_MIN . ' characters';
			return $ret;
		}
		
		if ($len > PASSWORD_LEN_MAX) {
			$ret->message = 'Password must be at most ' . PASSWORD_LEN_MAX  . ' characters';
			return $ret;
		}

		$ret->valid = true;
		$ret->message = '';
		return $ret;
	}
?>