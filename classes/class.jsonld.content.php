<?php
class ODG_JSONLD_Content {

	private function get_query( ) {
		$query = array(
			'post_type' => 'post',
			'posts_per_page' => 10,
		);

		// Security: Whitelist and sanitize allowed query parameters
		if ( isset( $_GET['filter'] ) && is_array( $_GET['filter'] ) ) {
			$allowed_params = array(
				'posts_per_page', 'post_type', 'post_status',
				'cat', 'category_name', 'tag', 'tag_id',
				'author', 'author_name',
				'year', 'monthnum', 'day', 'hour', 'minute', 'second',
				'name', 'pagename', 'page_id', 'p',
				'order', 'orderby',
				's', 'search', 'exact', 'sentence',
			);

			foreach ( $_GET['filter'] as $key => $value ) {
				if ( in_array( $key, $allowed_params, true ) ) {
					// Sanitize based on parameter type
					switch ( $key ) {
						case 'posts_per_page':
						case 'cat':
						case 'tag_id':
						case 'author':
						case 'year':
						case 'monthnum':
						case 'day':
						case 'hour':
						case 'minute':
						case 'second':
						case 'page_id':
						case 'p':
							$query[ $key ] = absint( $value );
							break;
						case 'order':
							$query[ $key ] = ( 'DESC' === strtoupper( $value ) ) ? 'DESC' : 'ASC';
							break;
						case 'orderby':
							$allowed_orderby = array( 'date', 'title', 'name', 'modified', 'author', 'rand', 'ID' );
							if ( in_array( $value, $allowed_orderby, true ) ) {
								$query[ $key ] = sanitize_key( $value );
							}
							break;
						default:
							$query[ $key ] = sanitize_text_field( $value );
							break;
					}
				}
			}
		}

		if ( ! isset( $query['posts_per_page'] ) ) {
			$query['posts_per_page'] = -1;
		}

		return $query;
	}


	public function get_content( $mapped_schema_array ) {
		$arg = $this->get_query();
		$WP_Content = new WP_Query( $arg );
		if ( ! $WP_Content->have_posts() ) {
			return false;
		} else {
			$i = 0;
			$content = $cont = [];
			while ( $WP_Content->have_posts() ) {
				$WP_Content->the_post();
				$post_meta = get_post_meta( get_the_ID() );
				$j = 0;
				foreach ( $mapped_schema_array as $mapped_schema ) {
					$type = null;
					foreach ( $mapped_schema as $key => $schema ) {
						if ( isset( $post_meta[ $key ] ) && $post_meta[ $key ][0] ) {
							$cont[ $i ][ $j ][ $schema ] = $post_meta[ $key ][0];
						}
						if ( '@type' === $key ) {
							$type = $schema;
						}
					}
					// Bug fix: Use $type instead of $schema
					if ( isset( $cont[ $i ][ $j ] ) && $type ) {
						$cont[ $i ][ $j ]['@type'] = $type;
					}
					$j++;
				}
				if ( isset( $cont[ $i ] ) ) {
					if ( 1 < count( $cont[ $i ] ) ) {
						// Bug fix: Merge $cont[$i] instead of entire $cont array
						$content[ $i ]['@graph'] = array_values( $cont[ $i ] );
					} else {
						$content[ $i ] = $cont[ $i ][0];
					}
					$content[ $i ]['@id'] = get_the_permalink();
					$content[ $i ]['@context'] = get_home_url() . '/odg-jsonld-context/';
				}
				$i++;
			}
			$content = array_merge( $content );
			return $content;
		}
	}
}
