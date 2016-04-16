<?php
class ODG_Admin_Schema {
	public function save_schema() {
		if ( check_admin_referer( 'odg-schema' ) ) {
			$e = new WP_Error();
			update_option( 'odg-context' ,  $this->check_schema() );
		} else {
			update_option( 'odg-context' , '' );
		}
		wp_safe_redirect( menu_page_url( 'odg-schema' , false ) );
	}

	private function check_schema()
	{
		$contextArr = $_POST['odg-schema'];
		foreach ( $contextArr as $key => $value ) {
			if ( array_filter( $value ) ) {
				$context[] = array_filter( $value );
			}
		}
		if ( ! $context ) {
			$context[0] = array(
				'type' => 'schema',
				'iri'  => 'http://schema.org/',
			);
		}
		return $context;
	}
}
