<?php
class ODG_Ep_Mapping {

	public function get_schema() {
		$Mappings = $this->get_mappings();
		if ( $Mappings->have_posts() ) {
			$i = 0;
			while ( $Mappings->have_posts() ) {
				$Mappings->the_post();
				$post_meta = get_post_meta( get_the_ID() );
				foreach ( $post_meta as $key => $value ) {
					if ( ! preg_match( '%^_%' , $key ) ) {
						$schema[ $i ][ $key ] = $value[0];
					}
				}
				$schema[ $i ]['@type'] = get_the_title();
				$i++;
			}
		}
		return $schema;
	}

	public function get_mappings() {
		$map_arg = array(
			'post_type' => ODG_Config::NAME,
		);
		$Mappings = new WP_Query( $map_arg );
		return $Mappings;
	}

	public function mapping_test( $Map ) {
		if ( $Map->have_posts() ) {
			while ( $Map->have_posts() ) {
				$Map->the_post();
				if ( ODG_Config::NAME !== get_post_type() ) {
					echo get_the_ID() . "is Error!!\n";
				} else {
					echo get_the_ID() . "is OK\n";
				}
			}
		}
	}
}
