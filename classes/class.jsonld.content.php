<?php
class ODG_JSONLD_Content {

	private function get_query( ) {
		$query = array(
			'post_type' => 'post',
			'posts_per_page' => 10,
		);
		if ( isset( $_GET['filter'] ) ) {
			$query = $_GET['filter'];
		}
		if ( ! isset( $query['posts_per_page'] ) ) {
			$query['posts_per_page'] = -1;
		}
		/*
		if(isset($wp_query->query_vars["category_name"])){
		  $query['category_name'] = $wp_query->query_vars["category_name"];
		}
		*/
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
					foreach ( $mapped_schema as $key => $schema ) {
						if ( isset( $post_meta[ $key ] ) && $post_meta[ $key ][0] ) {
							$cont[ $i ][ $j ][ $schema ] = $post_meta[ $key ][0];
						}
						if ( '@type' === $key ) {
							$type = $schema;
						}
					}
					if ( isset( $cont[ $i ][ $j ] ) ) {
						$cont[ $i ][ $j ]['@type'] = $schema;
					}
					$j++;
				}
				if ( isset( $cont[ $i ] ) ) {
					if ( 1 < count( $cont[ $i ] ) ) {
						$content[ $i ]['@graph'] = array_merge( $cont );
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
