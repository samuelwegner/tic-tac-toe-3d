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
 * This: server/util-create-game-turns.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Server-side utility for creating the "ttt3d_game_turns"
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
		
		$stmt = 'CREATE TABLE ttt3d_game_turns (
			game_id INT UNSIGNED NOT NULL,
			turn_num TINYINT UNSIGNED NOT NULL,
			player_num TINYINT UNSIGNED NOT NULL,
			turn_started TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			turn_ended TIMESTAMP,
			coord_x TINYINT UNSIGNED,
			coord_y TINYINT UNSIGNED,
			coord_z TINYINT UNSIGNED,
			CONSTRAINT pk_game_turns PRIMARY KEY (game_id, turn_num),
			CONSTRAINT fk_game_id FOREIGN KEY (game_id)
			REFERENCES ttt3d_games(game_id)
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
