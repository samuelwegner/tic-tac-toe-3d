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
 * This: server/util-create-games.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Server-side utility for creating the "ttt3d_games"
 *          MySQL database table for Tic-Tac-Toe 3D. This file should be
 *          removed from the server after the table is created.
 */

	header('Access-Control-Allow-Origin: *');
?>

<!DOCTYPE html>
<html>
<body>
    <?php
		include_once('./global.php');
		
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		
		if (!$conn) {
			die('Connection failed');
		}
		
		$stmt = 'CREATE TABLE ttt3d_games (
			game_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			is_full TINYINT(1) DEFAULT 0,
			is_complete TINYINT(1) DEFAULT 0,
			is_tied TINYINT(1) DEFAULT 0,
			winning_player VARCHAR(24),
			CONSTRAINT fk_winning_player FOREIGN KEY (winning_player)
			REFERENCES ttt3d_users(user_id)
		)';
		
        if ($conn->query($stmt)) {
            echo 'Success';
        }
        else {
            echo 'Error: ' . $conn->error;
        }

        $conn->close();
    ?>
</body>
</html>
