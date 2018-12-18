<?php
/**
 * Created by PhpStorm.
 * User: dmytro
 * Date: 6/13/18
 * Time: 17:17
 */

class UserLocation {

	CONST PLACE_API = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';
	CONST PLACE_API_QUERY = 'https://maps.googleapis.com/maps/api/place/textsearch/json?';
	CONST PLACE_ID_API = 'https://maps.googleapis.com/maps/api/place/details/json?';
	CONST PLACES_DISTANCE = 'https://maps.googleapis.com/maps/api/distancematrix/json?';

	private $latitude = 50.4501;
	private $longitude = 30.5234;
	private $key = 'AIzaSyCEuUGQiuTq9RPdPaIGKxJwpPWPxtDbRDo';
	private $language = 'en-GB';
	private $result_type = ''; // ROOFTOP
	private $location_type = ''; // street_address
	private $radius = 1000;
	private $type = '';
	private $query = '';
	private $limit;
	private $place_id = '';

	private $next_page_token = '';


	/**
	 * Google API Maps URL
	 * @var string
	 */
	private $api_url = 'https://maps.googleapis.com/maps/api/geocode/json?';
	private $place_api_url = '';


	/**
	 *
	 * @param $latitude
	 * @param $longitude
	 */
	public function __construct( $latitude, $longitude ) {
		$this->latitude  = $latitude;
		$this->longitude = $longitude;
	}

	public function set_latitude( $latitude ) {
		$this->latitude = $latitude;
	}


	public function set_longitude( $longitude ) {
		$this->longitude = $longitude;
	}

	/**
	 * Builds api url with parameters and stores in $api_url
	 */
	private function build_api_url( $route ) {

		if ( $route == 'location' ) {
			$params        = [
				'latlng'        => $this->latitude . ',' . $this->longitude,
				'key'           => $this->key,
				'language'      => $this->language,
				'result_type'   => $this->result_type,
				'location_type' => $this->location_type
			];
			$this->api_url = $this->api_url . http_build_query( $params );

		} elseif ( $route == 'place_id' ) {
			$params              = [
				'placeid' => $this->place_id,
				'key'     => $this->key,
			];
			$this->place_api_url = self::PLACE_ID_API . http_build_query( $params );
		} elseif ( $route == 'places_distance' ) {
			$params              = [
				'units'        => 'metric',
				'mode'         => 'walking',
				'origins'      => $this->latitude . ',' . $this->longitude,
				'destinations' => 'place_id:' . $this->place_id,
				'key'          => $this->key,
			];
			$this->place_api_url = self::PLACES_DISTANCE . http_build_query( $params );
		} elseif ( $this->query ) {
			$params              = [
				'location' => $this->latitude . ',' . $this->longitude,
				'key'      => $this->key,
				'language' => $this->language,
				'radius'   => $this->radius,
				'type'     => $this->type,
				'query'    => $this->query,
			];
			$this->place_api_url = self::PLACE_API_QUERY . http_build_query( $params );
		} else {
			$params              = [
				'location' => $this->latitude . ',' . $this->longitude,
				'key'      => $this->key,
				'language' => $this->language,
				'radius'   => $this->radius,
				'type'     => $this->type,
			];
			$this->place_api_url = self::PLACE_API . http_build_query( $params );
		}
	}

	private function make_curl_request( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$server_output = curl_exec( $ch );
		curl_close( $ch );

		return $server_output;
	}

	/**
	 * Returns the current location in string
	 * @return string
	 */
	public function get_location() {

		$this->build_api_url( 'location' );

		$url = $this->api_url;

		$server_output = $this->make_curl_request( $url );
		$result        = json_decode( $server_output, true );

		return $result['results'][0]['formatted_address'];
	}

	public function get_place_by_type( $type = '' ) {

		$this->type = $type;

		$this->build_api_url( $type );
		$url = $this->place_api_url;

		$server_output = $this->make_curl_request( $url );
		$result        = json_decode( $server_output, false );

		$places = [];

		foreach ( $result->results as $i => $place ) {

			if ( $this->limit === $i ) {
				break;
			}

			$place_id = $place->place_id;

			$website  = $this->get_place_info( $place_id, 'website' );
			$distance = $this->get_info_between_places( $place_id, 'distance' );
			$duration = $this->get_info_between_places( $place_id, 'duration' );

			$matrix_string = $distance && $duration ? ' | ' . $distance . " ($duration walk)" : '';

			$phone = "\r\n" . $this->get_place_info( $place_id, 'phone' );

			$rating   = $place->rating ?? 'rating is not set';
			$places[] = $i + 1 . '. <a href="' . $website . '">' . $place->name . "</a>, <strong>rating: " . $rating . '</strong> ' . $matrix_string . $phone;
		}

		return $places;
	}

	public function set_textsearch_query( $query = '' ) {
		$this->query = $query;
	}

	/**
	 *
	 * @param $types array A list of types array
	 * @param $limit integer
	 *
	 * @return array
	 */
	public function get_places_by_type( $types = [ 'restaurant', 'cafe', 'bar' ], $limit = 20 ) {
		$this->limit = $limit;
		$result      = [];
		for ( $i = 0; $i < count( $types ); $i ++ ) {
			$type            = $types[ $i ];
			$result[ $type ] = $this->get_place_by_type( $type );
		}

		return $result;
	}

	/**
	 * @param $type string
	 * @param $limit integer
	 *
	 * @return array
	 */
	public function print_textquery_places( $type, $limit ) {

		$this->limit = $limit;

		$result = $this->get_place_by_type( $type );

		return $result;

	}

	/**
	 * Get place details info
	 *
	 * @param $place_id string Place id from Google API
	 * @param $info string Key string what info needed
	 *
	 * @return string
	 */
	public function get_place_info( $place_id, $info = '' ) {

		$this->place_id = $place_id;

		$this->build_api_url( 'place_id' );

		$url = $this->place_api_url;

		$server_output = $this->make_curl_request( $url );
		$result        = json_decode( $server_output, false );
		$result_info   = '';

		if ( $result->status == "OK" ) {
			if ( $info == 'website' ) {
				$result_info = $result->result->website ?? $result->result->url;
			} elseif ( $info == 'phone' ) {
				$result_info = $result->result->international_phone_number ?? $result->result->formatted_phone_number;
			}
		}

		return $result_info;
	}

	/**
	 * Get info about places distance relations
	 *
	 * @param $place_id string Place id from Google API
	 * @param $info string Key string what info needed
	 *
	 * @return string
	 */
	public function get_info_between_places( $place_id, $info = '' ) {
		$this->place_id = $place_id;

		$this->build_api_url( 'places_distance' );

		$url = $this->place_api_url;

		$server_output = $this->make_curl_request( $url );
		$result        = json_decode( $server_output, false );
		$result_info   = '';

		if ( $result->status == "OK" ) {
			if ( $info == 'distance' ) {
				$result_info = $result->rows[0]->elements[0]->distance->text;
			} elseif ( $info == 'duration' ) {
				$result_info = $result->rows[0]->elements[0]->duration->text;
			}
		}

		return $result_info;
	}
}

//$curr_location = new UserLocation( 50.45466, 30.5238 );
//
////$curr_location->set_textsearch_query( 'noodle' );
////
////$result = $curr_location->print_textquery_places( 'restaurant', 10 );
//
//echo $curr_location->get_distance_between_places( 'ChIJuQRNNabP1EARdtcsVMxDmIQ' );
//
//var_dump( $result );