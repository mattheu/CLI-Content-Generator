<?php

class CLIImageGenerator {

	private $data = array();

	function get_data( $search ) {

		$search = str_replace( ' ', '+', $search );

		$api_url_base = 'http://api.flickr.com/services/rest/';

		$api_url_base = add_query_arg( array(
			'method' => 'flickr.interestingness.getList',
			'api_key' => MPH_GENERATOR_FLICKR_API_KEY,
			'format' => 'json',
			'nojsoncallback' => 1,
			'per_page' => 500,
		), $api_url_base );

		$hash = hash( 'md5', $api_url_base );

		if ( ! empty( $this->data[$hash] ) )
			return $this->data[$hash];

		$this->data[$hash] = array();

		$response = wp_remote_get( $api_url_base );
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );

		if ( $response && isset( $response->photos ) && isset( $response->photos->photo ) ) {

			foreach ( (array) $response->photos->photo as $photo ) {

				$title = $photo->title;

				if ( ! empty( $search ) )
					$title = $search . ' - ' . $title;

				$this->data[$hash][] = array(
					'title' => $title,
					'src' => sprintf(
						'http://farm%s.staticflickr.com/%s/%s_%s_b.jpg',
						$photo->farm,
						$photo->server,
						$photo->id,
						$photo->secret
					)

				);

			}

			set_transient( $hash, $this->data[$hash], 3600 );

		} else {

			return array();

		}

		return $this->data[$hash];

	}

	function get_image_srcs( $search, $count = 1 ) {

		$r = array();

		$photos = $this->get_data( $search );

		if ( empty( $photos ) )
			return $r;

		for ( $i=0; $i < $count; $i++ ) {

			$key = array_rand( $photos );
			$r[] = $photos[$key];
			unset( $photos[$key] );

		}

		return $r;

	}

}


//       public function sideload_image ( $src, $post_id = null, $desc = null ) {

		// 	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  //           require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  //           require_once(ABSPATH . "wp-admin" . '/includes/media.php');

		// 	if ( ! empty( $src ) ) {

		// 		// Fix issues with double encoding
		// 		$src = urldecode( $src );

		// 		// Set variables for storage
		// 		// fix src filename for query strings
		// 		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $src, $matches);

		// 		if ( empty( $matches ) )
		// 			return false;

		// 		// Download file to temp location
		// 		$tmp = download_url( $src );

		// 		$file_array = array();
		// 		$file_array['name'] = basename($matches[0]);
		// 		$file_array['tmp_name'] = $tmp;

		// 		// If error storing temporarily, unlink
		// 		if ( is_wp_error( $tmp ) ) {
		// 			@unlink($file_array['tmp_name']);
		// 			$file_array['tmp_name'] = '';
		// 			return false;
		// 		}

		// 		// do the validation and storage stuff
		// 		$id = media_handle_sideload( $file_array, $post_id, $desc );

		// 		// If error storing permanently, unlink
		// 		if ( is_wp_error($id) ) {
		// 			@unlink($file_array['tmp_name']);
		// 			return false;
		// 		}

		// 		return $id;

		// 	}

		// 	return false;

  //       }