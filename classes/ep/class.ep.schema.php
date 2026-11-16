<?php
class ODG_Ep_Schema {

	public function get_context() {
		$contextData = array();
		if ( get_option( 'odg-context' ) ) {
			$contextData = get_option( 'odg-context' );
		}
		$context = $this->get_context_data( $contextData );
		$context = json_encode( $context , JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		return $context;
	}

	private function get_context_data( $contextData ){
		switch ( count( $contextData ) ) {
			case 0:
				$context['@context'] = array(
					'schema' => 'http://schema.org/',
					);
				break;
			case 1:
				// esc_url() is for HTML output and turns "&" into "&#038;"; this is JSON.
				$context['@context'] = esc_url_raw( $contextData[0]['iri'] );
				break;
			default:
				foreach ( $contextData as $key => $value ) {
					$contextArray[] = array(
						esc_attr( $value['type'] ) => esc_url_raw( $value['iri'] ),
					);
				}
				$context['@context'] = $contextArray;
				break;
		}
		return $context;
	}
}
