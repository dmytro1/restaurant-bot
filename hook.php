<?php
/**
 * Created by PhpStorm.
 * User: dmytro
 * Date: 6/11/18
 * Time: 12:01
 */

include( 'vendor/autoload.php' );
include( 'includes/PrettyResponse.php' );
include( 'includes/UserLocation.php' );
include( 'includes/Products.php' );
include( 'includes/Database.php' );


use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;

$telegram = new Api( '570752214:AAEHXtycph97x8Z6EW8RTPgFBeE2-sLSFdQ' );
$conn     = new Database(
	'elanding.mysql.tools',
	'elanding_db',
	'V7VljKcF',
	'elanding_db'
);

$limit = 3;

try {
	$result = $telegram->getWebhookUpdate();

	$reply_markup = Keyboard::make( [
		'resize_keyboard'   => true,
		'one_time_keyboard' => false,
	] )->row(
		Keyboard::button(
			[ 'text' => 'Send location', 'request_location' => true ]
		)
	);

	$reply_markup_inline = Keyboard::make()->inline()->row(
		Keyboard::inlineButton(
			[ 'text' => 'Pizza', 'callback_data' => 'pizza' ]
		),
		Keyboard::inlineButton(
			[ 'text' => 'Noodle', 'callback_data' => 'noodle' ]
		),
		Keyboard::inlineButton(
			[ 'text' => 'Burger', 'callback_data' => 'burger' ]
		)
	)->row(
		Keyboard::inlineButton(
			[ 'text' => 'Sushi', 'callback_data' => 'sushi' ]
		),
		Keyboard::inlineButton(
			[ 'text' => 'Salad', 'callback_data' => 'salad' ]
		),
		Keyboard::inlineButton(
			[ 'text' => 'Meat & Steak', 'callback_data' => 'meat' ]
		)
	);

	if ( $result->isType( 'callback_query' ) ) {

		$callback_data = $result->callbackQuery->data;
		$chat_id       = $result->getChat()->id;
		$message_id    = $result->getMessage()->messageId;
		$message_text  = $result->getMessage()->text;

		if ( $callback_data == "pizza" || $callback_data == "noodle"
		     || $callback_data == "burger"
		     || $callback_data == "sushi"
		     || $callback_data == "salad"
		     || $callback_data == "meat"
		) {

			$telegram->sendMessage(
				[
					'chat_id'    => $chat_id,
					'parse_mode' => 'HTML',
					'text'       => 'Wait a while, information loading ⏳',
				]
			);

			$latitude  = $conn->get_latitude( $chat_id );
			$longitude = $conn->get_longitude( $chat_id );

			$curr_location = new UserLocation( $latitude, $longitude );

			$curr_location->set_textsearch_query( $callback_data );
			$places = $curr_location->print_textquery_places( 'restaurant',
				$limit );

			$telegram->sendMessage(
				[
					'chat_id'    => $chat_id,
					'parse_mode' => 'HTML',
					'text'       => 'Top ' . $limit . ' <strong>"' . strtoupper(
							$callback_data
						) . '" </strong>places:',
				]
			);

			$telegram->sendMessage(
				[
					'chat_id'                  => $chat_id,
					'parse_mode'               => 'HTML',
					'text'                     => implode( "\r\n\r\n",
						$places ),
					'disable_web_page_preview' => true,
					'reply_markup'             => $reply_markup_inline,
				]
			);

		} elseif ( $callback_data == "" ) {
			$telegram->sendMessage(
				[
					'chat_id'      => $chat_id,
					'text'         => 'Callback is empty',
					'reply_markup' => $reply_markup,
				]
			);
		} else {
			$reply = "Nothing found on this callback: " . "<strong>\""
			         . $callback_data . "\"</strong>";
			$telegram->sendMessage(
				[
					'chat_id'      => $chat_id,
					'parse_mode'   => 'HTML',
					'text'         => $reply,
					'reply_markup' => $reply_markup,
				]
			);
		}

	} elseif ( $result->isType( 'message' ) ) {

		$message    = $result->getMessage();
		$location   = $message->location;
		$text       = $message->text;
		$chat_id    = $message->chat->id;
		$first_name = $message->from->firstName;
		$last_name  = $message->from->lastName;
		$username   = $message->from->username;

		if ( $text == "/start" ) {

			$reply_row_1 = "Hi, " . "<strong>" . $first_name
			               . "!</strong> I'm restaurant bot 🙂";
			$reply_row_2
			             = 'I help you to find the restaurant nearby your location and your tastes 👍';
			$reply_row_3
			             = 'Please send your location and I\'ll start to find it 👇';

			$telegram->sendMessage(
				[
					'chat_id'      => $chat_id,
					'text'         => $reply_row_1,
					'parse_mode'   => 'html',
					'reply_markup' => $reply_markup,
				]
			);

			$telegram->sendMessage(
				[
					'chat_id'      => $chat_id,
					'text'         => $reply_row_2,
					'parse_mode'   => 'html',
					'reply_markup' => $reply_markup,
				]
			);

			$telegram->sendMessage(
				[
					'chat_id'      => $chat_id,
					'text'         => $reply_row_3,
					'parse_mode'   => 'html',
					'reply_markup' => $reply_markup,
				]
			);

			if ( $conn->check_connection() ) {

				$chats_id = $conn->get_chats_id();

				if ( in_array( $chat_id, $chats_id ) ) {
					$reply = 'This user already exists';
				} else {
					$reply = $conn->insert_new_user(
						$first_name,
						$last_name,
						$username,
						$chat_id
					);
				}

				//				$telegram->sendMessage( [
				//					'chat_id'      => $chat_id,
				//					'text'         => $reply,
				//					'parse_mode'   => 'HTML',
				//					'reply_markup' => $reply_markup
				//				] );

			}

		} elseif ( $location ) {

			// Current real location
			$latitude  = $location->latitude;
			$longitude = $location->longitude;

			// Maidan Kyiv location
			//			$latitude  = 50.45466;
			//			$longitude = 30.5238;

			$reply = '';

			if ( $conn->check_connection() ) {
				$reply = $conn->insert_location(
					$chat_id,
					$latitude,
					$longitude
				);
			} else {
				$reply = 'Location insert failed';
			}

			//			$telegram->sendMessage( [
			//				'chat_id'    => $chat_id,
			//				'text'       => $reply,
			//				'parse_mode' => 'HTML',
			//			] );

			$curr_location = new UserLocation( $latitude, $longitude );

			//			$telegram->sendMessage( [
			//				'chat_id'    => $chat_id,
			//				'parse_mode' => 'HTML',
			//				'text'       => PrettyResponse::print_response_string( $location )
			//			] );

			$telegram->sendMessage(
				[
					'chat_id'    => $chat_id,
					'parse_mode' => 'HTML',
					'text'       => "Your location is:\r\n<strong>"
					                . $curr_location->get_location()
					                . "</strong>",
				]
			);

			$telegram->sendMessage(
				[
					'chat_id'    => $chat_id,
					'parse_mode' => 'HTML',
					'text'       => 'Wait a while, information loading ⏳',
				]
			);

			$places = $curr_location->get_places_by_type(
				[ 'restaurant', 'cafe', 'bar' ],
				$limit
			);

			foreach ( $places as $i => $place ) {
				$telegram->sendMessage(
					[
						'chat_id'    => $chat_id,
						'parse_mode' => 'HTML',
						'text'       => 'Top ' . $limit . ' <strong>'
						                . strtoupper( $i )
						                . 'S:</strong>',
					]
				);

				$telegram->sendMessage(
					[
						'chat_id'                  => $chat_id,
						'parse_mode'               => 'HTML',
						'text'                     => implode(
							"\r\n\r\n",
							$place
						),
						'disable_web_page_preview' => true,
					]
				);
			}

			$telegram->sendMessage(
				[
					'chat_id'      => $chat_id,
					'text'         => 'What kind of food you prefer ☕️🍕🍔 ?',
					'reply_markup' => $reply_markup_inline,
				]
			);

		} elseif ( $text == "" ) {
			$telegram->sendMessage(
				[
					'chat_id'    => $chat_id,
					'parse_mode' => 'HTML',
					'text'       => 'Empty message ',
				]
			);
			$telegram->sendMessage(
				[
					'chat_id'    => $chat_id,
					'text'       => PrettyResponse::print_response_string(
						$result
					),
					'parse_mode' => 'HTML',
				]
			);
		} else {
			$reply = "Nothing found on this query: " . "<strong>\"" . $text
			         . "\"</strong>.\r\nTry this button 👇";
			$telegram->sendMessage(
				[
					'chat_id'      => $chat_id,
					'parse_mode'   => 'HTML',
					'text'         => $reply,
					'reply_markup' => $reply_markup,
				]
			);
		}
	}
} catch ( Exception $e ) {
	echo $e;
}