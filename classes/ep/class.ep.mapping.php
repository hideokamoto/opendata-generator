<?php
class ODG_Ep_Mapping {

    public function get_Mappings () {
        $map_arg = array(
          "post_type" =>  ODG_Config::NAME
        );
        $Mappings = new WP_Query( $map_arg );
        return $Mappings;
      }

    public function mapping_test($Map) {
        if( $Map->have_posts() ){
            while ( $Map->have_posts() ) {
                $Map->the_post();
                if(  ODG_Config::NAME !== get_post_type()){
                    echo get_the_ID() . "is Error!!\n";
                } else {
                    echo get_the_ID() . "is OK\n";
                }
            }
        }
    }
}
