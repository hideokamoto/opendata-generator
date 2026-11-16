<?php
class ODG_Admin_Schema {
	public function save_schema() {
		// The settings page is only shown to users who can manage options; the save must
		// check the same capability, not just the nonce.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'opendata-generator' ), 403 );
		}

		// Verify nonce for security
		if ( check_admin_referer( 'odg-schema' ) ) {
			$context = $this->check_schema();
			$updated = update_option( 'odg-context', $context );

			// Add admin notice based on result
			if ( $updated ) {
				add_settings_error(
					'odg-schema',
					'odg-schema-updated',
					__( 'Schema settings saved successfully.', 'opendata-generator' ),
					'updated'
				);
			}
		} else {
			// Nonce verification failed
			add_settings_error(
				'odg-schema',
				'odg-schema-error',
				__( 'Security check failed. Please try again.', 'opendata-generator' ),
				'error'
			);
		}

		// Redirect back to settings page
		set_transient( 'settings_errors', get_settings_errors(), 30 );
		if ( wp_safe_redirect( add_query_arg( 'settings-updated', 'true', menu_page_url( 'odg-schema', false ) ) ) ) {
			// Without exit the request kept running and rendered admin-init output after the redirect header.
			exit;
		}
	}

	private function check_schema()
	{
		$context = array();

		// Security: Validate POST data exists and is array
		if ( ! isset( $_POST['odg-schema'] ) || ! is_array( $_POST['odg-schema'] ) ) {
			$context[0] = array(
				'type' => 'schema',
				'iri'  => 'http://schema.org/',
			);
			return $context;
		}

		$contextArr = $_POST['odg-schema'];
		foreach ( $contextArr as $key => $value ) {
			if ( is_array( $value ) && array_filter( $value ) ) {
				// Sanitize type and IRI
				$sanitized = array();
				if ( isset( $value['type'] ) && ! empty( $value['type'] ) ) {
					$sanitized['type'] = sanitize_text_field( $value['type'] );
				}
				if ( isset( $value['iri'] ) && ! empty( $value['iri'] ) ) {
					// Validate and sanitize URL
					$iri = esc_url_raw( $value['iri'] );
					if ( filter_var( $iri, FILTER_VALIDATE_URL ) ) {
						$sanitized['iri'] = $iri;
					}
				}
				// Only add if both type and IRI are valid
				if ( isset( $sanitized['type'] ) && isset( $sanitized['iri'] ) ) {
					$context[] = $sanitized;
				}
			}
		}

		// Default to schema.org if no valid context
		if ( empty( $context ) ) {
			$context[0] = array(
				'type' => 'schema',
				'iri'  => 'http://schema.org/',
			);
		}

		return $context;
	}
}
