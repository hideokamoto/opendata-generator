<?php
class ODG_Admin_Panels {

    public function odg_admin_menu(){
          $home_url = get_home_url();
          echo "<div class='wrap'>";
          printf( "<h2>%s</h2>" , __( "OpenData Generator", ODG_Config::NAME ) );
          printf( "<p>%s</p>" , __( "Now This Plugin Supports only JSON-LD.", ODG_Config::NAME ) );

          echo "<table class='widefat form-table'>";

          //How To Use Table
          printf( "<thead><tr><th colspan='2'>　%s</th></tr>",
          __( "How To Use(JSON-LD)" , ODG_Config::NAME ) );
          echo "</thead>";
          echo "<tbody>";
            printf("<tr><td>　%s</td><td><a href='{$home_url}/odg-jsonld/' target='_blank'>{$home_url}/odg-jsonld/</a></td></tr>",
            __( "See All Data" , ODG_Config::NAME ) );
            printf( "<tr><td>　%s</td><td>{$home_url}/[POST_URL]/odg-jsonld/</td></tr>" ,
            __( "See Single Post Data" , ODG_Config::NAME ) );
            printf( "<tr><td>　%s</td><td><a href='{$home_url}/odg-jsonld/' target='_blank'>{$home_url}/odg-jsonld/</a></td></tr>" ,
            __( "Search Data for All Post" , ODG_Config::NAME ) ) ;
          echo "</tbody>";

          //Settings Tables
          printf( "<thead><tr><th colspan='2'>　%s</th></tr></thead>",
          __( "Settings" , ODG_Config::NAME ) );
          echo "<tbody><tr><td colspan='2'>";
            printf( "%s<hr>", __( 'Schema' , ODG_Config::NAME ) );
            _e( "<p>RDFの語彙PREFIXを指定するよ。<br/>デフォルトでは「http://schema.org/」が指定されてるよ。</p>", ODG_Config::NAME );
            echo "<br/>";
            printf( "%s<hr>", __( 'Mapping' , ODG_Config::NAME ) );
            _e( "<p>カスタムフィールドとのマッピングを行うよ。<br/>ここでマッピングしないと表示されないよ</p>" , ODG_Config::NAME );
          echo "</td></tr></tbody>";
          echo "</table>";
          echo "</div>";
    }

    public function odg_admin_schema() {
        $contexts = $this->get_schema_context();
        echo "<div class='wrap'>";
        printf( "<h2>%s</h2>" , __( 'Make JSON-LD' , ODG_Config::NAME ) );
        printf( "<h3>%s</h3>" , __( 'Setting Vocabulary' , ODG_Config::NAME ) );
        echo "<form method='post' action='' novalidate='novalidate'>";
        wp_nonce_field( 'odg-schema');
        echo "<table class='widefat form-table'><thead>";
        printf(
          "<tr><th>　%s</th><th>URI</th></tr>",
          __('Vocabulary Name',ODG_Config::NAME)
        );
        echo "</thead>";
        $this->show_schema_tables( $contexts );
        echo "</table>";
        $this->show_save_button( );
        echo "</form>";
        echo "</div>";
    }

    private function get_schema_context( ) {
        $contexts = get_option('odg-context');

        if (!$contexts)
            $contexts[0] = array(
                "type" =>"schema",
                "iri"  =>"http://schema.org/"
            );

        return $contexts;
    }

    private function show_schema_tables( $contexts ) {
        $i = 0;
        $tbody = "<tbody>";
        foreach( $contexts as  $context ){
            if ( $context['type'] ) {
                $input_type_name  = "odg-schema[{$i}][type]";
                $input_type_value = esc_attr( $context['type'] );
                $input_iri_name   = "odg-schema[{$i}][iri]";
                $input_iri_value  = esc_url( $context['iri'] );
                $tr  = "<tr>";
                $tr .= "<td><input name='{$input_type_name}' type='text' id='vocabulary-{$i}' value='{$input_type_value}' class='regular-text code'></td>";
                $tr .= "<td><input name='$input_iri_name' type='url' id='siteurl-{$i}' value='{$input_iri_value}' class='regular-text code'></td>";
                $tr .="</tr>";
                $tbody .= $tr;
                $i++;
            }
        }
        $tr  = "<tr>";
        $tr .= "<td><input name='odg-schema[{$i}][type]' type='text' id='vocabulary-{$i}' value='' class='regular-text code'></td>";
        $tr .= "<td><input name='odg-schema[{$i}][iri]' type='url' id='siteurl-{$i}' value='' class='regular-text code'></td>";
        $tr .="</tr>";
        $tbody .= $tr;
        $tbody .= "<tbody>";
        echo $tbody;
    }

    public function show_save_button( ) {
        echo "<p class='submit'>";
        printf(
          "<input type='submit' class='button button-primary' value='%s'>",
          __('Save Change',ODG_Config::NAME)
        );
        echo "</p>";
    }
}
