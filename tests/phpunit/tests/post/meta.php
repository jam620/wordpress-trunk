<?php

/**
 * @group post
 * @group meta
 */
class Tests_Post_Meta extends WP_UnitTestCase {

	private $last_register_meta_call = array(
		'object_type' => '',
		'meta_key'    => '',
		'args'        => array(),
	);

	protected static $author;
	protected static $post_id;
	protected static $post_id_2;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$author = $factory->user->create_and_get( array( 'role' => 'editor' ) );

		self::$post_id = $factory->post->create(
			array(
				'post_author'  => self::$author->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
			)
		);

		self::$post_id_2 = $factory->post->create(
			array(
				'post_author'  => self::$author->ID,
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
			)
		);
	}

	public static function wpTearDownAfterClass() {
		wp_delete_post( self::$post_id, true );
		wp_delete_post( self::$post_id_2, true );
		self::delete_user( self::$author );
	}

	function test_unique_postmeta() {
		// Add a unique post meta item.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'unique', 'value', true ) );

		// Check unique is enforced.
		$this->assertFalse( add_post_meta( self::$post_id, 'unique', 'another value', true ) );

		// Check it exists.
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'unique', true ) );
		$this->assertEquals( array( 'value' ), get_post_meta( self::$post_id, 'unique', false ) );

		// Fail to delete the wrong value.
		$this->assertFalse( delete_post_meta( self::$post_id, 'unique', 'wrong value' ) );

		// Delete it.
		$this->assertTrue( delete_post_meta( self::$post_id, 'unique', 'value' ) );

		// Check it is deleted.
		$this->assertEquals( '', get_post_meta( self::$post_id, 'unique', true ) );
		$this->assertEquals( array(), get_post_meta( self::$post_id, 'unique', false ) );

	}

	function test_nonunique_postmeta() {
		// Add two non-unique post meta items.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'nonunique', 'value' ) );
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'nonunique', 'another value' ) );

		// Check they exist.
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'nonunique', true ) );
		$this->assertEquals( array( 'value', 'another value' ), get_post_meta( self::$post_id, 'nonunique', false ) );

		// Fail to delete the wrong value.
		$this->assertFalse( delete_post_meta( self::$post_id, 'nonunique', 'wrong value' ) );

		// Delete the first one.
		$this->assertTrue( delete_post_meta( self::$post_id, 'nonunique', 'value' ) );

		// Check the remainder exists.
		$this->assertEquals( 'another value', get_post_meta( self::$post_id, 'nonunique', true ) );
		$this->assertEquals( array( 'another value' ), get_post_meta( self::$post_id, 'nonunique', false ) );

		// Add a third one.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'nonunique', 'someother value' ) );

		// Check they exist.
		$expected = array(
			'someother value',
			'another value',
		);
		sort( $expected );
		$this->assertTrue( in_array( get_post_meta( self::$post_id, 'nonunique', true ), $expected, true ) );
		$actual = get_post_meta( self::$post_id, 'nonunique', false );
		sort( $actual );
		$this->assertEquals( $expected, $actual );

		// Delete the lot.
		$this->assertTrue( delete_post_meta_by_key( 'nonunique' ) );
	}

	function test_update_post_meta() {
		// Add a unique post meta item.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'unique_update', 'value', true ) );

		// Add two non-unique post meta items.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'nonunique_update', 'value' ) );
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'nonunique_update', 'another value' ) );

		// Check they exist.
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'unique_update', true ) );
		$this->assertEquals( array( 'value' ), get_post_meta( self::$post_id, 'unique_update', false ) );
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'nonunique_update', true ) );
		$this->assertEquals( array( 'value', 'another value' ), get_post_meta( self::$post_id, 'nonunique_update', false ) );

		// Update them.
		$this->assertTrue( update_post_meta( self::$post_id, 'unique_update', 'new', 'value' ) );
		$this->assertTrue( update_post_meta( self::$post_id, 'nonunique_update', 'new', 'value' ) );
		$this->assertTrue( update_post_meta( self::$post_id, 'nonunique_update', 'another new', 'another value' ) );

		// Check they updated.
		$this->assertEquals( 'new', get_post_meta( self::$post_id, 'unique_update', true ) );
		$this->assertEquals( array( 'new' ), get_post_meta( self::$post_id, 'unique_update', false ) );
		$this->assertEquals( 'new', get_post_meta( self::$post_id, 'nonunique_update', true ) );
		$this->assertEquals( array( 'new', 'another new' ), get_post_meta( self::$post_id, 'nonunique_update', false ) );

	}

	function test_delete_post_meta() {
		// Add two unique post meta items.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'unique_delete', 'value', true ) );
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id_2, 'unique_delete', 'value', true ) );

		// Check they exist.
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'unique_delete', true ) );
		$this->assertEquals( 'value', get_post_meta( self::$post_id_2, 'unique_delete', true ) );

		// Delete one of them.
		$this->assertTrue( delete_post_meta( self::$post_id, 'unique_delete', 'value' ) );

		// Check the other still exists.
		$this->assertEquals( 'value', get_post_meta( self::$post_id_2, 'unique_delete', true ) );

	}

	function test_delete_post_meta_by_key() {
		// Add two unique post meta items.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'unique_delete_by_key', 'value', true ) );
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id_2, 'unique_delete_by_key', 'value', true ) );

		// Check they exist.
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'unique_delete_by_key', true ) );
		$this->assertEquals( 'value', get_post_meta( self::$post_id_2, 'unique_delete_by_key', true ) );

		// Delete one of them.
		$this->assertTrue( delete_post_meta_by_key( 'unique_delete_by_key' ) );

		// Check the other still exists.
		$this->assertEquals( '', get_post_meta( self::$post_id_2, 'unique_delete_by_key', true ) );
		$this->assertEquals( '', get_post_meta( self::$post_id_2, 'unique_delete_by_key', true ) );
	}

	function test_get_post_meta_by_id() {
		$mid = add_post_meta( self::$post_id, 'get_post_meta_by_key', 'get_post_meta_by_key_value', true );
		$this->assertInternalType( 'integer', $mid );

		$mobj             = new stdClass;
		$mobj->meta_id    = $mid;
		$mobj->post_id    = self::$post_id;
		$mobj->meta_key   = 'get_post_meta_by_key';
		$mobj->meta_value = 'get_post_meta_by_key_value';
		$this->assertEquals( $mobj, get_post_meta_by_id( $mid ) );
		delete_metadata_by_mid( 'post', $mid );

		$mid = add_post_meta( self::$post_id, 'get_post_meta_by_key', array( 'foo', 'bar' ), true );
		$this->assertInternalType( 'integer', $mid );
		$mobj->meta_id    = $mid;
		$mobj->meta_value = array( 'foo', 'bar' );
		$this->assertEquals( $mobj, get_post_meta_by_id( $mid ) );
		delete_metadata_by_mid( 'post', $mid );
	}

	function test_delete_meta() {
		$mid = add_post_meta( self::$post_id, 'delete_meta', 'delete_meta_value', true );
		$this->assertInternalType( 'integer', $mid );

		$this->assertTrue( delete_meta( $mid ) );
		$this->assertFalse( get_metadata_by_mid( 'post', $mid ) );

		$this->assertFalse( delete_meta( 123456789 ) );
	}

	function test_update_meta() {
		// Add a unique post meta item.
		$this->assertInternalType( 'integer', $mid1 = add_post_meta( self::$post_id, 'unique_update', 'value', true ) );

		// Add two non-unique post meta items.
		$this->assertInternalType( 'integer', $mid2 = add_post_meta( self::$post_id, 'nonunique_update', 'value' ) );
		$this->assertInternalType( 'integer', $mid3 = add_post_meta( self::$post_id, 'nonunique_update', 'another value' ) );

		// Check they exist.
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'unique_update', true ) );
		$this->assertEquals( array( 'value' ), get_post_meta( self::$post_id, 'unique_update', false ) );
		$this->assertEquals( 'value', get_post_meta( self::$post_id, 'nonunique_update', true ) );
		$this->assertEquals( array( 'value', 'another value' ), get_post_meta( self::$post_id, 'nonunique_update', false ) );

		// Update them.
		$this->assertTrue( update_meta( $mid1, 'unique_update', 'new' ) );
		$this->assertTrue( update_meta( $mid2, 'nonunique_update', 'new' ) );
		$this->assertTrue( update_meta( $mid3, 'nonunique_update', 'another new' ) );

		// Check they updated.
		$this->assertEquals( 'new', get_post_meta( self::$post_id, 'unique_update', true ) );
		$this->assertEquals( array( 'new' ), get_post_meta( self::$post_id, 'unique_update', false ) );
		$this->assertEquals( 'new', get_post_meta( self::$post_id, 'nonunique_update', true ) );
		$this->assertEquals( array( 'new', 'another new' ), get_post_meta( self::$post_id, 'nonunique_update', false ) );

		// Slashed update.
		$data = "'quote and \slash";
		$this->assertTrue( update_meta( $mid1, 'unique_update', addslashes( $data ) ) );
		$meta = get_metadata_by_mid( 'post', $mid1 );
		$this->assertEquals( $data, $meta->meta_value );
	}

	/**
	 * @ticket 12860
	 */
	function test_funky_post_meta() {
		$classy          = new StdClass();
		$classy->ID      = 1;
		$classy->stringy = 'I love slashes\\\\';
		$funky_meta[]    = $classy;

		$classy          = new StdClass();
		$classy->ID      = 2;
		$classy->stringy = 'I love slashes\\\\ more';
		$funky_meta[]    = $classy;

		// Add a post meta item.
		$this->assertInternalType( 'integer', add_post_meta( self::$post_id, 'test_funky_post_meta', $funky_meta, true ) );

		// Check it exists.
		$this->assertEquals( $funky_meta, get_post_meta( self::$post_id, 'test_funky_post_meta', true ) );

	}

	/**
	 * @ticket 38323
	 * @dataProvider data_register_post_meta
	 */
	public function test_register_post_meta( $post_type, $meta_key, $args ) {
		add_filter( 'register_meta_args', array( $this, 'filter_register_meta_args_set_last_register_meta_call' ), 10, 4 );

		register_post_meta( $post_type, $meta_key, $args );

		$args['object_subtype'] = $post_type;

		// Reset global so subsequent data tests do not get polluted.
		$GLOBALS['wp_meta_keys'] = array();

		$this->assertEquals( 'post', $this->last_register_meta_call['object_type'] );
		$this->assertEquals( $meta_key, $this->last_register_meta_call['meta_key'] );
		$this->assertEquals( $args, $this->last_register_meta_call['args'] );
	}

	public function data_register_post_meta() {
		return array(
			array( 'post', 'registered_key1', array( 'single' => true ) ),
			array( 'page', 'registered_key2', array() ),
			array( '', 'registered_key3', array( 'sanitize_callback' => 'absint' ) ),
		);
	}

	public function filter_register_meta_args_set_last_register_meta_call( $args, $defaults, $object_type, $meta_key ) {
		$this->last_register_meta_call['object_type'] = $object_type;
		$this->last_register_meta_call['meta_key']    = $meta_key;
		$this->last_register_meta_call['args']        = $args;

		return $args;
	}

	/**
	 * @ticket 38323
	 * @dataProvider data_unregister_post_meta
	 */
	public function test_unregister_post_meta( $post_type, $meta_key ) {
		global $wp_meta_keys;

		register_post_meta( $post_type, $meta_key, array() );
		unregister_post_meta( $post_type, $meta_key );

		$actual = $wp_meta_keys;

		// Reset global so subsequent data tests do not get polluted.
		$wp_meta_keys = array();

		$this->assertEmpty( $actual );
	}

	public function data_unregister_post_meta() {
		return array(
			array( 'post', 'registered_key1' ),
			array( 'page', 'registered_key2' ),
			array( '', 'registered_key3' ),
		);
	}

	/**
	 * @ticket 44467
	 */
	public function test_add_metadata_sets_posts_last_changed() {
		$post_id = self::factory()->post->create();

		wp_cache_delete( 'last_changed', 'posts' );

		$this->assertInternalType( 'integer', add_metadata( 'post', $post_id, 'foo', 'bar' ) );
		$this->assertNotFalse( wp_cache_get_last_changed( 'posts' ) );
	}

	/**
	 * @ticket 44467
	 */
	public function test_update_metadata_sets_posts_last_changed() {
		$post_id = self::factory()->post->create();

		wp_cache_delete( 'last_changed', 'posts' );

		$this->assertInternalType( 'integer', update_metadata( 'post', $post_id, 'foo', 'bar' ) );
		$this->assertNotFalse( wp_cache_get_last_changed( 'posts' ) );
	}

	/**
	 * @ticket 44467
	 */
	public function test_delete_metadata_sets_posts_last_changed() {
		$post_id = self::factory()->post->create();

		update_metadata( 'post', $post_id, 'foo', 'bar' );
		wp_cache_delete( 'last_changed', 'posts' );

		$this->assertTrue( delete_metadata( 'post', $post_id, 'foo' ) );
		$this->assertNotFalse( wp_cache_get_last_changed( 'posts' ) );
	}
}
