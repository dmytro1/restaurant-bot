<?php
/**
 * Created by PhpStorm.
 * User: dmytro
 * Date: 6/14/18
 * Time: 17:45
 */

class Products {

	private $landing_url = 'https://e-landing.top/wp-json/wp/v2';
	private $title = '';
	private $type = '';
	private $media_id = null;
	private $source_url = '';
	private $variations_color = [];
	private $html = [];

	public function render(): array {
		$this->set_products_info();

		return $this->html;
	}

	private function set_products_info() {
		$url           = $this->landing_url . '/product';
		$server_output = $this->make_curl_request( $url );
		$products      = json_decode( $server_output, false );


		foreach ( $products as $i => $product ) {

			$this->title            = $product->title->rendered;
			$this->type             = $product->type;
			$this->media_id         = $product->featured_media;
			$this->variations_color = $product->variations_color;
			$this->set_image_url();
			$this->render_html( $i );
		}
	}

	private function set_image_url() {
		$media_id = $this->media_id;
		$url      = $this->landing_url . '/media/' . $media_id;
		if ( $media_id ) {

			$server_output    = $this->make_curl_request( $url );
			$media            = json_decode( $server_output, false );
			$this->source_url = $media->source_url;
		}
	}

	private function render_html( $i ) {
		$this->html[]     .= $this->title . "\r\n";
		$this->html[ $i ] .= $this->type . "\r\n";
		$this->html[ $i ] .= $this->media_id . "\r\n";
		$this->html[ $i ] .= $this->source_url . "\r\n";
		$this->html[ $i ] .= is_array( $this->variations_color ) ? implode( ', ', $this->variations_color ) . "\r\n" : "Colors variations not found\r\n";
		$this->html[ $i ] .= '<a href="' . $this->source_url . '">&#8205;</a>' . "\r\n";
	}

	private function make_curl_request( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$server_output = curl_exec( $ch );
		curl_close( $ch );

		return $server_output;
	}
}