<?php
/**
 * Created by PhpStorm.
 * User: dmytro
 * Date: 7/18/18
 * Time: 16:36
 */

class Database {
	protected $connection;

	public function __construct( $servername, $username, $password, $dbname ) {
		$this->connection = new mysqli( $servername, $username, $password, $dbname );
	}

	public function check_connection() {
		// Check connection
		if ( $this->connection->connect_error ) {
			die( "Connection failed: " . mysqli_connect_error() );
		}

		return true;
	}

	public function get_chats_id() {
		$sql      = "SELECT chat_id FROM bot_users_info";
		$result   = $this->connection->query( $sql );
		$chats_id = [];

		if ( $result->num_rows > 0 ) {
			// output data of each row
			while ( $row = $result->fetch_assoc() ) {
				$chats_id[] = $row["chat_id"];
			}
		} else {
//			echo "0 results";
		}

		return $chats_id;

	}

	public function insert_new_user( $firstname, $lastname, $username, $chat_id ) {
		$sql = "INSERT INTO bot_users_info (firstname, lastname, username, chat_id)
				VALUES ('$firstname', '$lastname', '$username', '$chat_id')";

		if ( $this->connection->query( $sql ) === true ) {
			return "New record created successfully";
		} else {
			return "Error: " . $sql . "<br>" . $this->connection->error;
		}
//		$this->connection->close();
	}

	public function insert_location( $chat_id, $latitude, $longitude ) {
		$sql = "UPDATE bot_users_info 
				SET latitude=$latitude, longitude=$longitude 
				WHERE chat_id = $chat_id";

		if ( $this->connection->query( $sql ) === true ) {
			return "Location ($latitude, $longitude) inserted successfully";
		} else {
			return "Error: " . $sql . "<br>" . $this->connection->error;
		}
	}

	public function get_latitude( $chat_id ) {
		$sql    = "SELECT latitude 
					 FROM bot_users_info
					 WHERE chat_id = $chat_id";
		$result = $this->connection->query( $sql );

		$latitude = mysqli_fetch_object( $result )->latitude;

		return $latitude;
	}

	public function get_longitude( $chat_id ) {
		$sql    = "SELECT longitude 
					 FROM bot_users_info
					 WHERE chat_id = $chat_id";
		$result = $this->connection->query( $sql );

		$longitude = mysqli_fetch_object( $result )->longitude;

		return $longitude;
	}
}

//include_once 'UserLocation.php';
//
//$conn     = new Database( 'elanding.mysql.tools', 'elanding_db', 'V7VljKcF', 'elanding_db' );
//
//$latitude  = $conn->get_latitude( 76852895 );
//$longitude = $conn->get_longitude( 76852895 );
//
//$curr_location = new UserLocation( $latitude, $longitude );
//
//$curr_location->set_textsearch_query( 'pizza' );
//
//$places = $curr_location->get_places_by_type( [ 'restaurant', 'cafe', 'bar' ], 5 );
//var_dump($places);