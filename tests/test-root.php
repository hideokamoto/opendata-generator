<?php

class OpenData_Generator_Test extends WP_UnitTestCase {

	private $post_id;

	/**
	 * Setup test environment
	 */
	public function setUp() {
		parent::setUp();

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
		$mapping      = new ODG_Ep_Mapping();
		$schema_array = $mapping->get_schema();

		// Simulate valid filter parameter
		$_GET['filter'] = array(
			'posts_per_page' => '5',
			'order'          => 'DESC',
		);

		$jsonld = new ODG_JSONLD_Content();
		$content = $jsonld->get_content( $schema_array );

		$this->assertIsArray( $content );

		// Clean up
		unset( $_GET['filter'] );
	}

	/**
	 * Test security: Invalid filter parameters should be sanitized
	 */
	public function test_jsonld_content_security_sanitization() {
		$mapping      = new ODG_Ep_Mapping();
		$schema_array = $mapping->get_schema();

		// Simulate malicious filter parameter
		$_GET['filter'] = array(
			'posts_per_page' => '-1; DROP TABLE wp_posts;', // SQL injection attempt
			'order'          => 'INVALID',
			'unknown_param'  => 'should_be_ignored',
		);

		$jsonld = new ODG_JSONLD_Content();
		$content = $jsonld->get_content( $schema_array );

		// Should still work without errors
		$this->assertIsArray( $content );

		// Clean up
		unset( $_GET['filter'] );
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
				'type' => 'invalid',
				'iri'  => 'not-a-url',
			),
		);

		$_POST['_wpnonce'] = wp_create_nonce( 'odg-schema' );

		$schema = new ODG_Admin_Schema();
		$result = $schema->save_schema();

		$context = get_option( 'odg-context' );

		// XSS attempt should be sanitized
		$this->assertStringNotContainsString( '<script>', $context[0]['type'] );

		// Invalid URL should be rejected
		$this->assertCount( 1, $context );

		// Clean up
		unset( $_POST['odg-schema'] );
		unset( $_POST['_wpnonce'] );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown() {
		wp_delete_post( $this->post_id, true );
		delete_option( 'odg-context' );
		parent::tearDown();
	}
}
