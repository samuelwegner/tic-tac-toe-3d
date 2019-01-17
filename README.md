# tic-tac-toe-3d

This project implements a 3-dimensional [tic-tac-toe](https://en.wikipedia.org/wiki/Tic-tac-toe) game. While the traditional 2D tic-tac-toe game is played on a 3x3 square grid, this 3D variant is played on a 3x3x3 cubic grid.

## Description

3D tic-tac-toe gameplay is equivalent to the 2D variant, except that a player can win by claiming spaces spanning the game grid in a straight line through any spatial dimensions. Consequently, there are up to 13 possible win vectors for a given move, depending on where that move coordinate falls within the game grid.

The current implementation is based around a time-limited, turn-based, exclusive-game model. This means that a player is only allowed to participate in one game at a time, and a player will forfeit their turn if they wait too long to act after their turn starts (60 seconds per turn by default). There is a basic matchmaking system to add players to open games and create more games as needed.

The game may be configured to use a grid size of up to 6x6x6 and more than 2 players by modifying the relevant constants defined in the [server/game.php](server/game.php) file. However, the current implementation stores the grid size and maximum player number in server-wide constants, so these settings cannot be selected on a per-game basis. These settings should not be modified after the game server is in production. A possible future enhancement would be to store grid size and maximum player count in database records so these could be configured differently from one game to another.

The game is designed around a client-server architecture using PHP web services accessed with HTTP POST client requests and JSON-encoded message contents for both requests and responses. The game was originally developed as part of a college course on mobile application development. The client-side application was developed by another student; this repository currently only includes the server-side application developed by Samuel Wegner.

The game also has a rudimentary user authentication system. While this login system is adequate as a proof of concept, it should not be considered secure and would need additional security features for production use. Currently, user accounts exist primarily for the purpose of tracking win/loss/tie statistics.

## Installing

Files in the **server** folder should be uploaded to your Web server. The server must have PHP version 5.5+ and MySQL DBMS installed. PHP version 7+ is recommended.

The include statements in the PHP files use relative paths, so the files should work regardless of where they are located on the server, as long as all of the files are in the same directory. If you want to store the PHP files in multiple directories, it may be necessary to modify the include paths in these files.

The [global.php](server/global.php) file must be edited to reflect the database name, host, and user/password for your MySQL database. After configuring the database constants, execute these files to create the necessary database tables:
* [util-create-users.php](server/util-create-users.php)
* [util-create-games.php](server/util-create-games.php)
* [util-create-game-players.php](server/util-create-game-players.php)
* [util-create-game-turns.php](server/util-create-game-turns.php)

Note that the PHP files prefixed with "util" are not part of the application itself, but are included for setup and debugging purposes. These files should not be accessible to the public, so they should either be removed after initial setup/debugging or should have access restricted.

## Playing

This repository currently only contains the server-side code for the game. To play the game, you will need a way to create and send HTTP POST requests and view the server responses. The [Postman](https://www.getpostman.com/) tool is useful for this purpose. Refer to the [API_summary](API_summary.txt) document for guidance on the purpose of the available services, how to create JSON request messages for each service, and how to interpret the server responses.

## Authors

[Samuel Wegner](https://github.com/samuelwegner/) - Project owner

## License

This project is licensed under the [GPL v3](LICENSE).
