<?php
/**
 * Created by PhpStorm.
 * User: dmytro
 * Date: 6/11/18
 * Time: 12:01
 */

include_once 'vendor/autoload.php';
include_once 'includes/PrettyResponse.php';
include_once 'includes/UserLocation.php';
include_once 'includes/Products.php';
include_once 'includes/Database.php';

use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\FileUpload\InputFile;

$telegram = new Api( '570752214:AAEHXtycph97x8Z6EW8RTPgFBeE2-sLSFdQ' );
$conn     = new Database( 'elanding.mysql.tools', 'elanding_db', 'V7VljKcF', 'elanding_db' );
//	$keyboard = [ ['7', '8'], ['4', '5', '6'], ['1', '2', '3'], ['0'] ];

try {
	$result = $telegram->getWebhookUpdate();

	if ( $result->isType( 'callback_query' ) ) {

		$callback_data = $result->callbackQuery->data;
		$chat_id       = $result->getChat()->id;
		$message_id    = $result->getMessage()->messageId;
		$message_text  = $result->getMessage()->text;

		if ( $callback_data == 'add_keyboard' ) {

			$reply_markup = Keyboard::make()->row(
				Keyboard::button( [ 'text' => 'Button' ] ),
				Keyboard::button( [ 'text' => 'Share your contact', 'request_contact' => true ] ),
				Keyboard::button( [ 'text' => 'Share your location', 'request_location' => true ] )
			);

			$telegram->sendMessage( [
				'chat_id'      => $chat_id,
				'text'         => 'Keyboard added',
				'reply_markup' => $reply_markup
			] );

		}
		elseif ( $callback_data == 'remove_keyboard' ) {
			$reply_markup = Keyboard::remove( [
				'remove_keyboard' => true,
				'selective'       => false,
			] );
			$telegram->sendMessage( [
				'chat_id'      => $chat_id,
				'text'         => 'Keyboard removed',
				'reply_markup' => $reply_markup
			] );
		}
		elseif ( $callback_data == 'add_counter' ) {

			$reply_markup_inline = Keyboard::make()->inline()->row(
				Keyboard::inlineButton( [
					'text'          => '-',
					'callback_data' => 'minus'
				] ),
				Keyboard::inlineButton( [
					'text'          => 0,
					'callback_data' => 'number'
				] ),
				Keyboard::inlineButton( [
					'text'          => '+',
					'callback_data' => 'plus'
				] )
			);

			$telegram->sendMessage( [
				'chat_id'      => $chat_id,
				'text'         => '0',
				'reply_markup' => $reply_markup_inline
			] );

		}
		elseif ( $callback_data == 'plus' ) {
			$message_text ++;

			$reply_markup_inline = Keyboard::make()->inline()->row(
				Keyboard::inlineButton( [
					'text'          => '-',
					'callback_data' => 'minus'
				] ),
				Keyboard::inlineButton( [
					'text'          => $message_text,
//						'text'          => rand(),
					'callback_data' => 'number'
				] ),
				Keyboard::inlineButton( [
					'text'          => '+',
					'callback_data' => 'plus'
				] )
			)->row(
				Keyboard::inlineButton( [
					'text'          => 'Send',
					'callback_data' => $message_text
				] )
			);

			$telegram->editMessageText( [
				'chat_id'      => $chat_id,
				'message_id'   => $message_id,
				'text'         => $message_text,
				'reply_markup' => $reply_markup_inline
			] );

		}
		elseif ( $callback_data == 'minus' ) {

			$message_text --;

			$reply_markup_inline = Keyboard::make()->inline()->row(
				Keyboard::inlineButton( [
					'text'          => '-',
					'callback_data' => 'minus'
				] ),
				Keyboard::inlineButton( [
					'text'          => $message_text,
					'callback_data' => 'number'
				] ),
				Keyboard::inlineButton( [
					'text'          => '+',
					'callback_data' => 'plus'
				] )
			)->row(
				Keyboard::inlineButton( [
					'text'          => 'Send',
					'callback_data' => $message_text
				] )
			);

			if ( $message_text < 1 ) {
				// is negative
				if ( $message_text ) {
					return;
				}
				$reply_markup_inline = Keyboard::make()->inline()->row(
					Keyboard::inlineButton( [
						'text'          => '-',
						'callback_data' => 'minus'
					] ),
					Keyboard::inlineButton( [
						'text'          => $message_text,
						'callback_data' => 'number'
					] ),
					Keyboard::inlineButton( [
						'text'          => '+',
						'callback_data' => 'plus'
					] )
				);
			}

			$telegram->editMessageText( [
				'chat_id'      => $chat_id,
				'message_id'   => $message_id,
				'text'         => $message_text,
				'reply_markup' => $reply_markup_inline
			] );
		}
		elseif ( is_numeric( $callback_data ) && $callback_data > 0 ) {
//				$result->recentMessage();
			$telegram->sendMessage( [
				'chat_id' => $chat_id,
				'text'    => 'Your choice is: ' . $callback_data,
			] );
		}
		elseif ( $callback_data == "" ) {
			$telegram->sendMessage( [ 'chat_id' => $chat_id, 'text' => 'Callback is empty' ] );
		}
		else {
				$reply = "Nothing found on this callback: " . "<strong>\"" . $callback_data . "\"</strong>";
				$telegram->sendMessage( [ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => $reply ] );
			}

	} elseif ( $result->isType( 'message' ) ) {

		$message    = $result->getMessage();
		$location   = $message->location;
		$text       = $message->text;
		$chat_id    = $message->chat->id;
		$first_name = $message->from->firstName;
		$last_name  = $message->from->lastName;
		$username   = $message->from->username;
		$keyboard = [ [ "last_news" ], [ "_image_" ], [ "_gif_" ], [ "_test_btn_" ] ];

		if ( $location ) {

			$latitude  = $location->latitude;
			$longitude = $location->longitude;

			$curr_location = new UserLocation( $latitude, $longitude );

			$telegram->sendMessage( [
				'chat_id'    => $chat_id,
				'parse_mode' => 'HTML',
				'text'       => PrettyResponse::print_response_string( $location )
			] );
			$telegram->sendMessage( [
				'chat_id'    => $chat_id,
				'parse_mode' => 'HTML',
				'text'       => 'Your location is: ' . $curr_location->get_location()
			] );

		}
		elseif ( $text == "/start" ) {

			$params = [
				'keyboard'          => $keyboard,
				'resize_keyboard'   => false,
				'one_time_keyboard' => false
			];

			$reply_markup = Keyboard::make( $params );

			$reply = "Hi " . "<strong>" . $first_name . " " . $last_name . " (@" . $username . ")</strong>";


			$telegram->sendMessage( [
				'chat_id'      => $chat_id,
				'text'         => $reply,
				'parse_mode'   => 'HTML',
				'reply_markup' => $reply_markup
			] );


			if ( $conn->check_connection() ) {

				$chats_id = $conn->get_chats_id();

				if ( in_array( $chat_id, $chats_id ) ) {
					$reply = 'This user already exists';
				} else {
					$reply = $conn->insert_new_user( $first_name, $last_name, $username, $chat_id );
				}

				$telegram->sendMessage( [
					'chat_id'      => $chat_id,
					'text'         => $reply,
					'parse_mode'   => 'HTML',
					'reply_markup' => $reply_markup
				] );

			}

		}
		elseif ( $text == "/keyboard" ) {
			$reply               = "Inline buttons for add/remove Custom Keyboard";
			$reply_markup_inline = Keyboard::make()->inline()->row(
				Keyboard::inlineButton( [
					'text'          => 'Add Keyboard',
					'callback_data' => 'add_keyboard'
				] ),
				Keyboard::inlineButton( [
					'text'          => 'Remove Keyboard',
					'callback_data' => 'remove_keyboard'
				] ),
				Keyboard::inlineButton( [
					'text'          => 'Add counter',
					'callback_data' => 'add_counter'
				] )
			);

			$telegram->sendMessage( [
				'chat_id'      => $chat_id,
				'text'         => $reply,
				'reply_markup' => $reply_markup_inline
			] );
		}
		elseif ( $text == "_image_" ) {
			$url = "https://68.media.tumblr.com/6d830b4f2c455f9cb6cd4ebe5011d2b8/tumblr_oj49kevkUz1v4bb1no1_500.jpg";
			$telegram->sendPhoto( [
				'chat_id' => $chat_id,
				'photo'   => InputFile::create( $url ),
				'caption' => "Description"
			] );
		}
		elseif ( $text == "_gif_" ) {
			$url = "https://68.media.tumblr.com/bd08f2aa85a6eb8b7a9f4b07c0807d71/tumblr_ofrc94sG1e1sjmm5ao1_400.gif";
			$telegram->sendDocument( [
				'chat_id'  => $chat_id,
				'document' => InputFile::create( $url ),
				'caption'  => "Description"
			] );
		}
		elseif ( $text == "last_news" ) {
			$html = simplexml_load_file( 'https://nachasi.com/feed' );
			foreach ( $html->channel->item as $item ) {
				$reply .= "\xE2\x9E\xA1 " . $item->title . " (<a href='" . $item->link . "'>read more ..</a>)\n";
			}
			$telegram->sendMessage( [
				'chat_id'                  => $chat_id,
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => true,
				'text'                     => $reply
			] );
		}
		elseif ( $text == "/products" ) {

			$telegram->sendMessage( [ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => 'Products info' ] );

			$products     = new Products();
			$products_arr = $products->render();

			for ( $i = 0; $i < count( $products_arr ); $i ++ ) {
				$telegram->sendMessage( [
					'chat_id'    => $chat_id,
					'parse_mode' => 'HTML',
					'text'       => $products_arr[ $i ]
				] );
			}

		}
		elseif ( $text == "" ) {
			$telegram->sendMessage( [ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => 'Empty message ' ] );
			$telegram->sendMessage( [
				'chat_id'    => $chat_id,
				'text'       => PrettyResponse::print_response_string( $result ),
				'parse_mode' => 'HTML'
			] );
		}
		else {
			$reply = "Nothing found on this query: " . "<strong>\"" . $text . "\"</strong>";
			$telegram->sendMessage( [ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => $reply ] );
		}
	}
} catch ( Exception $e ) {
	echo $e;
}