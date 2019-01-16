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
 * This: server/util-view-game-turns.php
 * Date: 16-Jan-2019
 * Author: Samuel Wegner (samuelwegner@hotmail.com)
 * Purpose: Server-side utility for viewing records from the
 *          "ttt3d_game_turns" MySQL database table for Tic-Tac-Toe 3D.
 *          This file is provided for debugging purposes and should not be
 *          stored long-term on a public server.
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

		$stmt = 'SELECT * FROM ttt3d_game_turns ORDER BY game_id DESC, turn_num DESC';
		$result = $conn->query($stmt);
		$count = $result->num_rows;
		
		if ($count > 0) {
			echo 'Found ' . $count . ' rows:<br><br>';
			while ($row = $result->fetch_assoc()) {
				echo 'game_id: ' . $row['game_id']
					. ',  turn_num: ' . $row['turn_num']
					. ',  player_num: ' . $row['player_num']
					. ',  xyz: ( ' . $row['coord_x']
					. ', ' . $row['coord_y']
					. ', ' . $row['coord_z']
					. ' ),  turn_started: ' . $row['turn_started']
					. ',  turn_ended: ' . $row['turn_ended']
					. '<br>';
			}
		}
		else {
			echo 'Found 0 rows';
		}
		
		$result->free();
		
        $conn->close();
    ?>
</body>
</html>
