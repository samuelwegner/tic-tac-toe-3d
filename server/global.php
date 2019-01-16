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
 * This: server/global.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Library containing server-side global constants for Tic-Tac-Toe 3D.
 *          Note that the database constants (prefixed with "DB_") must be
 *          modified to reflect your server's configuration.
 */

	const DB_HOST = 'enterDatabaseHost'; // Database host name/address (e.g., "localhost")
	const DB_USER = 'enterDatabaseUser'; // Database login username
	const DB_PASSWORD = 'enterDatabasePassword'; // Database login password
	const DB_NAME = 'enterDatabaseName'; // Database name
	
	// Default error message for server responses
	const ERROR_MSG_GENERIC = 'The server encountered an error. Please try again later.';
?>