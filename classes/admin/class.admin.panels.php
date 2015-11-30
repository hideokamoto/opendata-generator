<?php
class ODG_Admin_Panels {

	public function odg_admin_menu(){
		$home_url = get_home_url();
		echo "<div class='wrap'>";
		printf( '<h2>%s</h2>' , __( 'OpenData Generator', 'opendata-generator' ) );
		printf( '<p>%s</p>' , __( 'Now This Plugin Supports only JSON-LD.', 'opendata-generator' ) );

		echo "<table class='widefat form-table'>";

		//How To Use Table
		printf( "<thead><tr><th colspan='2'>　%s</th></tr>",
		__( 'How To Use(JSON-LD)' , 'opendata-generator' ) );
		echo '</thead>';
		echo '<tbody>';
		printf("<tr><td>　%s</td><td><a href='{$home_url}/odg-jsonld/' target='_blank'>{$home_url}/odg-jsonld/</a></td></tr>",
		__( 'See All Data' , 'opendata-generator' ) );
		printf( "<tr><td>　%s</td><td>{$home_url}/[POST_URL]/odg-jsonld/</td></tr>" ,
		__( 'See Single Post Data' , 'opendata-generator' ) );
		printf( "<tr><td>　%s</td><td><a href='{$home_url}/odg-jsonld/' target='_blank'>{$home_url}/odg-jsonld/</a></td></tr>" ,
		__( 'Search Data for All Post' , 'opendata-generator' ) );
		echo '</tbody>';

		//Settings Tables
		printf( "<thead><tr><th colspan='2'>　%s</th></tr></thead>",
		__( 'Settings' , 'opendata-generator' ) );
		echo "<tbody><tr><td colspan='2'>";
		printf( '%s<hr>', __( 'Schema' , 'opendata-generator' ) );
		_e( '<p>Setting RDF Schem PREFIX.<br/>Default Setting is only 「http://schema.org/」.</p>', 'opendata-generator' );
		echo '<br/>';
		printf( '%s<hr>', __( 'Mapping' , 'opendata-generator' ) );
		_e( '<p>Mapping Custome Field Name and RDF Schema.<br/>Only showed mapped content.</p>' , 'opendata-generator' );
		echo '</td></tr></tbody>';
		echo '</table>';
		echo '</div>';
	}

	public function odg_admin_schema() {
		$contexts = $this->get_schema_context();
		echo "<div class='wrap'>";
		printf( '<h2>%s</h2>' , __( 'OpenData Generator' , 'opendata-generator' ) );
		printf( '<h3>%s</h3>' , __( 'Setting RDF Schema' , 'opendata-generator' ) );
		echo "<form method='post' action='' novalidate='novalidate'>";
		wp_nonce_field( 'odg-schema' );
		echo "<table class='widefat form-table'><thead>";
		printf(
			'<tr><th>　%s</th><th>URI</th></tr>',
			__( 'Vocabulary Name' , 'opendata-generator' )
		);
		echo '</thead>';
		$this->show_schema_tables( $contexts );
		echo '</table>';
		$this->show_save_button( );
		echo '</form>';
		echo '</div>';
	}

	private function get_schema_context( ) {
		$contexts = get_option( 'odg-context' );

		if ( ! $contexts ) {
			$contexts[0] = array(
				'type' => 'schema',
				'iri'  => 'http://schema.org/',
			);
		}

		return $contexts;
	}

	private function show_schema_tables( $contexts ) {
		$i = 0;
		$tbody = '<tbody>';
		foreach ( $contexts as $context ) {
			if ( $context['type'] ) {
				$input_type_name  = "odg-schema[{$i}][type]";
				$input_type_value = esc_attr( $context['type'] );
				$input_iri_name   = "odg-schema[{$i}][iri]";
				$input_iri_value  = esc_url( $context['iri'] );
				$tr  = '<tr>';
				$tr .= "<td><input name='{$input_type_name}' type='text' id='vocabulary-{$i}' value='{$input_type_value}' class='regular-text code'></td>";
				$tr .= "<td><input name='$input_iri_name' type='url' id='siteurl-{$i}' value='{$input_iri_value}' class='regular-text code'></td>";
				$tr .= '</tr>';
				$tbody .= $tr;
				$i++;
			}
		}
		$tr  = '<tr>';
		$tr .= "<td><input name='odg-schema[{$i}][type]' type='text' id='vocabulary-{$i}' value='' class='regular-text code'></td>";
		$tr .= "<td><input name='odg-schema[{$i}][iri]' type='url' id='siteurl-{$i}' value='' class='regular-text code'></td>";
		$tr .= '</tr>';
		$tbody .= $tr;
		$tbody .= '<tbody>';
		echo $tbody;
	}

	public function show_save_button( ) {
		echo "<p class='submit'>";
		printf(
			"<input type='submit' class='button button-primary' value='%s'>",
			__( 'Save Change' , 'opendata-generator' )
		);
		echo '</p>';
	}
}
