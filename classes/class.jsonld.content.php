<?php
class ODG_JSONLD_Content {

    public function get_content( $mapped_schema_array ) {
        $arg = array(
            "post_type" =>  'post',
            'posts_per_page' => 10
        );
        $WP_Content = new WP_Query( $arg );
        if ( !$WP_Content->have_posts()) {
          return false;
        } else {
            $i = 0;
            $cont = [];
            while ( $WP_Content->have_posts() ) {
                $WP_Content->the_post();
                $post_meta = get_post_meta( get_the_ID() ) ;
                foreach ($mapped_schema_array as $mapped_schema) {
                    foreach ($mapped_schema as $key => $schema) {
                        if ( isset( $post_meta[$schema] ) && $post_meta[$schema][0]) {
                            $cont[$i][$key] = $post_meta[$schema][0];
                        }
                    }
                }
                if ( isset( $cont[$i] ) ) {
                  $cont[$i]['@id'] = get_the_permalink();
                  $cont[$i]['@context'] = get_home_url() . "/odg-jsonld-context/";
                }
                $i++;
            }
            $cont = array_merge($cont);
            return $cont;
        }
    }
}
