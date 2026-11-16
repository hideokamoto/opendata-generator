<?php

class OpenData_Generator_Test extends WP_UnitTestCase {

	private $post_id;

	/**
	 * Setup test environment
	 *
	 * set_up()/tear_down() are the WordPress test suite's PHPUnit-version-neutral
	 * hooks (WP 5.9+). Overriding setUp() without `: void` is a fatal error there.
	 */
	public function set_up() {
		parent::set_up();

		// Create a test post
		$this->post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			)
		);

		// Add custom field for testing
		add_post_meta( $this->post_id, 'schema:name', 'Test Event' );
		add_post_meta( $this->post_id, 'schema:description', 'Test Description' );

		// Create mapping post
		$mapping_id = $this->factory->post->create(
			array(
				'post_title'  => 'Event',
				'post_type'   => ODG_Config::NAME,
				'post_status' => 'publish',
			)
		);
		add_post_meta( $mapping_id, 'schema:name', 'name' );
		add_post_meta( $mapping_id, 'schema:description', 'description' );
	}

	/**
	 * Clean up after tests
	 */
	public function tear_down() {
		unset( $_GET['filter'], $_POST['odg-schema'], $_POST['_wpnonce'], $_REQUEST['_wpnonce'] );
		wp_delete_post( $this->post_id, true );
		delete_option( 'odg-context' );
		parent::tear_down();
	}

	/**
	 * Run the JSON-LD query as the public endpoint does.
	 */
	private function get_public_content( $filter = null ) {
		if ( null !== $filter ) {
			$_GET['filter'] = $filter;
		}
		$mapping = new ODG_Ep_Mapping();
		$jsonld  = new ODG_JSONLD_Content();
		return $jsonld->get_content( $mapping->get_schema() );
	}

	private function create_post_with_name( $name, $args = array() ) {
		$post_id = $this->factory->post->create(
			array_merge(
				array(
					'post_title'  => $name,
					'post_status' => 'publish',
				),
				$args
			)
		);
		add_post_meta( $post_id, 'schema:name', $name );
		return $post_id;
	}

	/**
	 * Test config constants
	 */
	public function test_config_constants() {
		$this->assertEquals( 'opendata-generator', ODG_Config::NAME );
		$this->assertEquals( 'Opendata Generator', ODG_Config::DISPNAME );
		$this->assertEquals( 'odg-', ODG_Config::PREFIX );
	}

	/**
	 * Test ODG_Ep_Mapping class
	 */
	public function test_mapping_get_schema() {
		$mapping = new ODG_Ep_Mapping();
		$schema  = $mapping->get_schema();

		$this->assertIsArray( $schema );
		$this->assertNotEmpty( $schema );

		// Check if mapping contains expected keys
		$this->assertArrayHasKey( '@type', $schema[0] );
		$this->assertEquals( 'Event', $schema[0]['@type'] );
	}

	/**
	 * Test ODG_Ep_Schema context generation
	 */
	public function test_schema_get_context() {
		// Set test context
		update_option(
			'odg-context',
			array(
				array(
					'type' => 'schema',
					'iri'  => 'http://schema.org/',
				),
			)
		);

		$schema  = new ODG_Ep_Schema();
		$context = $schema->get_context();

		$this->assertIsString( $context );

		// Decode JSON to verify structure
		$decoded = json_decode( $context, true );
		$this->assertIsArray( $decoded );
		$this->assertArrayHasKey( '@context', $decoded );
	}

	/**
	 * Test ODG_JSONLD_Content with query parameters
	 */
	public function test_jsonld_content_with_valid_filter() {
		$content = $this->get_public_content(
			array(
				'posts_per_page' => '5',
				'order'          => 'DESC',
			)
		);

		$this->assertIsArray( $content );
		$this->assertSame( 'Test Event', $content[0]['name'] );
	}

	/**
	 * Test security: Invalid filter parameters should be sanitized
	 */
	public function test_jsonld_content_security_sanitization() {
		$content = $this->get_public_content(
			array(
				'posts_per_page' => '-1; DROP TABLE wp_posts;', // SQL injection attempt
				'order'          => 'INVALID',
				'unknown_param'  => 'should_be_ignored',
			)
		);

		// Should still work without errors
		$this->assertIsArray( $content );
		$this->assertNotNull( get_post( $this->post_id ) );
	}

	/**
	 * Drafts, private, pending, future and password-protected posts must never be
	 * served by the public endpoint, whatever post_status the request asks for.
	 */
	public function test_non_public_posts_are_never_exposed() {
		$this->create_post_with_name( 'SECRET-DRAFT', array( 'post_status' => 'draft' ) );
		$this->create_post_with_name( 'SECRET-PRIVATE', array( 'post_status' => 'private' ) );
		$this->create_post_with_name( 'SECRET-PENDING', array( 'post_status' => 'pending' ) );
		$this->create_post_with_name(
			'SECRET-FUTURE',
			array(
				'post_status' => 'future',
				'post_date'   => gmdate( 'Y-m-d H:i:s', time() + YEAR_IN_SECONDS ),
			)
		);
		$this->create_post_with_name( 'SECRET-PASSWORD', array( 'post_password' => 'pass' ) );

		foreach ( array( null, 'draft', 'private', 'pending', 'future', 'any', 'draft,private' ) as $status ) {
			$filter  = null === $status ? null : array( 'post_status' => $status );
			$content = $this->get_public_content( $filter );
			$json    = wp_json_encode( $content );

			$this->assertStringNotContainsString( 'SECRET-', $json, 'post_status=' . var_export( $status, true ) );
			$this->assertStringContainsString( 'Test Event', $json, 'post_status=' . var_export( $status, true ) );
			unset( $_GET['filter'] );
		}
	}

	/**
	 * post_type may only name post types that are public on the front end.
	 */
	public function test_post_type_filter_rejects_non_public_types() {
		register_post_type( 'odg_internal', array( 'public' => false ) );
		$this->create_post_with_name( 'SECRET-INTERNAL', array( 'post_type' => 'odg_internal' ) );

		foreach ( array( 'odg_internal', ODG_Config::NAME, 'revision', 'nav_menu_item' ) as $post_type ) {
			$json = wp_json_encode( $this->get_public_content( array( 'post_type' => $post_type ) ) );
			$this->assertStringNotContainsString( 'SECRET-INTERNAL', $json, 'post_type=' . $post_type );
			// The mapping definition ("schema:name" => "name") must not be served as content.
			$this->assertStringContainsString( 'Test Event', $json, 'post_type=' . $post_type );
			unset( $_GET['filter'] );
		}

		$this->factory->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => 'A page',
				'post_status' => 'publish',
				'meta_input'  => array( 'schema:name' => 'PUBLIC-PAGE' ),
			)
		);
		$json = wp_json_encode( $this->get_public_content( array( 'post_type' => 'page' ) ) );
		$this->assertStringContainsString( 'PUBLIC-PAGE', $json );
	}

	/**
	 * A post that matches only a later mapping keeps its fields.
	 */
	public function test_single_mapping_post_keeps_fields_when_it_matches_a_later_mapping() {
		$place_id = $this->factory->post->create(
			array(
				'post_title'  => 'Place',
				'post_type'   => ODG_Config::NAME,
				'post_status' => 'publish',
				// Older than the "Event" mapping, so get_schema() (newest first) lists it second.
				'post_date'   => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ),
			)
		);
		add_post_meta( $place_id, 'schema:address', 'address' );

		$post_id = $this->factory->post->create(
			array(
				'post_title'  => 'Address only',
				'post_status' => 'publish',
			)
		);
		add_post_meta( $post_id, 'schema:address', 'ADDRESS-ONLY' );

		$mapping = new ODG_Ep_Mapping();
		$schema  = $mapping->get_schema();
		$this->assertCount( 2, $schema );
		$this->assertSame( 'Place', $schema[1]['@type'] );

		$content = $this->get_public_content();
		$by_id   = array();
		foreach ( $content as $item ) {
			$by_id[ $item['@id'] ] = $item;
		}
		$item = $by_id[ get_permalink( $post_id ) ];

		// Its only entry sits at mapping index 1, not 0; the fields must survive.
		$this->assertSame( 'ADDRESS-ONLY', $item['address'] );
		$this->assertSame( 'Place', $item['@type'] );
	}

	/**
	 * Array values in filter[] must not reach string functions (TypeError on PHP 8).
	 */
	public function test_non_scalar_filter_values_are_ignored() {
		$content = $this->get_public_content(
			array(
				'order'   => array( 'DESC' ),
				'orderby' => array( 'title' ),
				's'       => array( 'x' ),
			)
		);

		$this->assertIsArray( $content );
	}

	/**
	 * Test ODG_Admin_Schema sanitization
	 */
	public function test_admin_schema_sanitization() {
		$_POST['odg-schema'] = array(
			array(
				'type' => 'test<script>alert("xss")</script>',
				'iri'  => 'http://example.com/',
			),
			array(
				// esc_url_raw() drops the javascript: scheme, so this row is rejected.
				'type' => 'invalid',
				'iri'  => 'javascript:alert(1)',
			),
		);

		// Only administrators may save; the nonce is tied to the current user.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// check_admin_referer() reads the nonce from $_REQUEST.
		$_POST['_wpnonce']    = wp_create_nonce( 'odg-schema' );
		$_REQUEST['_wpnonce'] = $_POST['_wpnonce'];

		// save_schema() redirects; stop wp_redirect() from sending headers in CLI.
		add_filter( 'wp_redirect', '__return_false' );

		$schema = new ODG_Admin_Schema();
		$schema->save_schema();

		remove_filter( 'wp_redirect', '__return_false' );

		$context = get_option( 'odg-context' );

		// XSS attempt should be sanitized
		$this->assertStringNotContainsString( '<script>', $context[0]['type'] );

		// Invalid URL should be rejected
		$this->assertCount( 1, $context );
	}

	/**
	 * A user without manage_options cannot save the schema, even with a valid nonce.
	 */
	public function test_admin_schema_requires_manage_options() {
		update_option( 'odg-context', array( array( 'type' => 'schema', 'iri' => 'http://schema.org/' ) ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$_POST['odg-schema']  = array( array( 'type' => 'evil', 'iri' => 'http://evil.example/' ) );
		$_POST['_wpnonce']    = wp_create_nonce( 'odg-schema' );
		$_REQUEST['_wpnonce'] = $_POST['_wpnonce'];

		$died = false;
		try {
			( new ODG_Admin_Schema() )->save_schema();
		} catch ( WPDieException $e ) {
			$died = true;
		}

		$this->assertTrue( $died );
		$this->assertSame( 'http://schema.org/', get_option( 'odg-context' )[0]['iri'] );
	}

	/**
	 * With no mapping defined, get_schema() returns an empty array (it used to read an
	 * undefined variable, a warning on PHP 8).
	 */
	public function test_mapping_get_schema_without_mappings() {
		foreach ( get_posts( array( 'post_type' => ODG_Config::NAME, 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $id ) {
			wp_delete_post( $id, true );
		}

		$mapping = new ODG_Ep_Mapping();
		$this->assertSame( array(), $mapping->get_schema() );
	}

	/**
	 * Every mapping applies, not only the 10 newest.
	 */
	public function test_mapping_get_schema_returns_more_than_ten_mappings() {
		for ( $i = 0; $i < 11; $i++ ) {
			$id = $this->factory->post->create(
				array(
					'post_title'  => 'Type' . $i,
					'post_type'   => ODG_Config::NAME,
					'post_status' => 'publish',
				)
			);
			add_post_meta( $id, 'schema:prop' . $i, 'prop' . $i );
		}

		$mapping = new ODG_Ep_Mapping();
		$this->assertCount( 12, $mapping->get_schema() );
	}

	/**
	 * The context endpoint is JSON: IRIs must not be HTML-escaped.
	 */
	public function test_context_iri_is_not_html_escaped() {
		update_option( 'odg-context', array( array( 'type' => 'ex', 'iri' => 'http://example.com/vocab?a=1&b=2' ) ) );

		$decoded = json_decode( ( new ODG_Ep_Schema() )->get_context(), true );
		$this->assertSame( 'http://example.com/vocab?a=1&b=2', $decoded['@context'] );
	}

	/**
	 * Building JSON-LD must not leave the global $post pointing at the last queried post.
	 */
	public function test_get_content_restores_global_post() {
		global $post;
		$other = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $other ) );
		// wp_reset_postdata() restores the main query's post, as on a real request.
		$post = $GLOBALS['wp_query']->post;
		$this->assertSame( $other, $post->ID );

		$this->get_public_content();

		$this->assertSame( $other, $GLOBALS['post']->ID );
	}
}
