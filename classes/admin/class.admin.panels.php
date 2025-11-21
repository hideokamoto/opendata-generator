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
		printf( "<tr><td>　%s</td><td><a href='{$home_url}/odg-jsonld-context/' target='_blank'>{$home_url}/odg-jsonld-context/</a></td></tr>" ,
		__( 'See Context Definition' , 'opendata-generator' ) );
		echo '</tbody>';

		//Settings Tables
		printf( "<thead><tr><th colspan='2'>　%s</th></tr></thead>",
		__( 'Settings' , 'opendata-generator' ) );
		echo "<tbody><tr><td colspan='2'>";
		printf( '%s<hr>', __( 'Schema' , 'opendata-generator' ) );
		_e( '<p>Setting RDF Schema PREFIX.<br/>Default Setting is only 「http://schema.org/」.</p>', 'opendata-generator' );
		echo '<br/>';
		printf( '%s<hr>', __( 'Mapping' , 'opendata-generator' ) );
		_e( '<p>Mapping Custom Field Name and RDF Schema.<br/>Only showed mapped content.</p>' , 'opendata-generator' );
		echo '</td></tr></tbody>';
		echo '</table>';

		// Preview Section
		$this->show_preview_section();

		echo '</div>';
	}

	private function show_preview_section() {
		printf( '<h3>%s</h3>', __( 'JSON-LD Preview', 'opendata-generator' ) );
		echo '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">';

		// Get sample data
		$mapping = new ODG_Ep_Mapping();
		$mapped_schema = $mapping->get_schema();

		if ( ! empty( $mapped_schema ) ) {
			$jsonld_content = new ODG_JSONLD_Content();
			$content = $jsonld_content->get_content( $mapped_schema );

			if ( $content ) {
				$json_preview = json_encode(
					array_slice( $content, 0, 2 ), // Show only first 2 items
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				);
				echo '<pre style="background: white; padding: 15px; overflow-x: auto; max-height: 400px;">';
				echo esc_html( $json_preview );
				echo '</pre>';
				printf(
					'<p><em>%s</em></p>',
					__( 'This is a preview of the first 2 items. View full output at the URL above.', 'opendata-generator' )
				);
			} else {
				printf(
					'<p>%s</p>',
					__( 'No posts found with mapped fields. Create posts with custom fields matching your mappings.', 'opendata-generator' )
				);
			}
		} else {
			printf(
				'<p>%s</p>',
				__( 'No mapping defined. Please create a mapping first.', 'opendata-generator' )
			);
		}

		echo '</div>';
	}

	public function odg_admin_schema() {
		$contexts = $this->get_schema_context();
		echo "<div class='wrap'>";
		printf( '<h2>%s</h2>' , __( 'OpenData Generator' , 'opendata-generator' ) );
		printf( '<h3>%s</h3>' , __( 'Setting RDF Schema' , 'opendata-generator' ) );

		// Display admin notices
		settings_errors( 'odg-schema' );

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
