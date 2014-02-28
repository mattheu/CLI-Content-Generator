<?php

class CLIContentGenerator {

	private $strings = array();

	function construct() {
	}

	function probability( $probability = 0.5 ) {
		$factor = pow(10,3);
		return rand(0, $factor ) < $probability * ( $factor + 1.0 );
	}

	/**
	 * Normal Distribution Random Number.
	 * Get a (random) number within a normal distribution, within the standard deviation.
	 * @param  int  $mean  Mean
	 * @param  int  $stdev Standard Deviation
	 * @return int         Random Number
	 */
	function normal_rand( $mean, $stdev = 1 ) {

		// Add 3 random numbers between -1 and 1. To 10 decimal places.
		$factor = pow( 10, 10 );
		$rand =  ( mt_rand( 0, $factor ) / $factor ) * ( (rand(0,1) ) ? 1 : -1 );
		$rand += ( mt_rand( 0, $factor ) / $factor ) * ( (rand(0,1) ) ? 1 : -1 );
		$rand += ( mt_rand( 0, $factor ) / $factor ) * ( (rand(0,1) ) ? 1 : -1 );

		return intval( round( $rand * $stdev + $mean ) );

	}

	/**
	 * Return a random item from an array
	 * @param  Array  $array [description]
	 * @return [type]        [description]
	 */
	private function get_rand_array_value( Array $array ) {
		return $array[array_rand($array)];
	}

	public function get_lorem_strings( $length = false ) {

		if ( empty( $this->strings ) ) {
			$this->strings = mph_get_lorem_strings();
		}

		if ( $length ) {
			$r = array();
			for ( $i = 0;  $i < $length;  $i++) {
				array_push( $r, $this->strings[ array_rand( $this->strings ) ] );
			}
			return $r;
		}

		return $this->strings;

	}

	public function get_lorem_ipsum( $length ) {

		$strings = $this->get_lorem_strings();

		$r = array();
		for ( $i=0;  $i < $length;  $i++) {
			array_push( $r, $strings[ array_rand( $strings ) ] );
		}

		return implode( ' ', $r );

	}

	public function get_random_string( $length = 10 ) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}

	public	function get_post_content() {

		$content = array();

		$paragraph_count = $this->normal_rand( 10, 6 );

		for ( $i=0; $i <= $paragraph_count; $i++ ) {

			$content[] = $this->generate_html_paragraph();

			if ( $this->probability(0.2) && $i !== $paragraph_count && $i > 2 ) {
				$content[] = $this->generate_html_heading();
			}

			if ( $this->probability(0.2) && $i !== $paragraph_count ) {
				$content[] = $this->generate_html_list();
			}

		}

		return implode( "\r\r", $content );

	}

	private function generate_html_paragraph() {
		return sprintf( '<p>%s</p>', $this->get_lorem_ipsum( $this->normal_rand( 10, 8 ) ) );
	}

	private function generate_html_heading( $tag = 'rand' ) {

		if ( $tag === 'rand' ) {
			$tag = $this->get_rand_array_value( array( 'h2', 'h3', 'h3', 'h3', 'h3', 'h3', 'h4', 'h4', 'h5' ) );
		}

		return sprintf(
			'<%1$s>%2$s</%1$s>',
			$tag,
			$this->get_lorem_ipsum( 1 )
		);

	}

	private function generate_html_list( $tag = 'rand' ) {

		$lis = array();

		for ($i=0; $i <= $this->normal_rand( 4, 3 ); $i++) {
			$lis[] = sprintf( '<li>%s</li>', $this->get_lorem_ipsum( 1 ) );
		}

		if ( 'rand' === $tag ) {
			$tags = array( 'ul', 'ol' );
			$tag = $tags[ array_rand( $tags ) ];
		}

		return sprintf(
			'<%1$s>%2$s</%1$s>',
			$tag,
			implode( "\r", $lis )
		);

	}

	private function generate_html_image( $thumbnail = false, $size = null ) {

		// if ( ! $size )
		// 	$size = $this->get_rand_array_value( array(  'thumbnail', 'medium', 'medium', 'large', 'large', 'large' ) );

		// $alignment = $this->get_rand_array_value( array( 'left', 'right', 'left', 'right', 'none' ) );
		// $classes = sprintf( 'size-%s align%s', $size, $alignment );

		// return wp_get_attachment_image( $image_id, $size, false, array( 'class' => $classes ) );

	}

}