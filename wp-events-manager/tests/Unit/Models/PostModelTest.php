<?php
/**
 * Post model tests.
 *
 * @package WPEMS\Tests\Unit\Models
 */

namespace WPEMS\Tests\Unit\Models;

use Brain\Monkey\Functions;
use WPEMS\Databases\PostDB;
use WPEMS\Models\PostModel;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test base post model behavior.
 */
class PostModelTest extends TestCase {

	/**
	 * Reset database singleton.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( PostDB::class, 'instance', null );
	}

	/**
	 * It maps only known object properties.
	 *
	 * @return void
	 */
	public function test_map_to_object_maps_known_properties(): void {
		$model = new PostModel();

		$model->map_to_object(
			array(
				'ID'          => '12',
				'post_title'  => 'Mapped title',
				'unknown_key' => 'ignored',
			)
		);

		$this->assertSame( '12', $model->ID );
		$this->assertSame( 'Mapped title', $model->post_title );
		$this->assertFalse( property_exists( $model, 'unknown_key' ) );
	}

	/**
	 * It returns the ID as an integer.
	 *
	 * @return void
	 */
	public function test_get_id_returns_int(): void {
		$model = new PostModel( array( 'ID' => '42' ) );

		$this->assertSame( 42, $model->get_id() );
	}

	/**
	 * It delegates title lookup to WordPress.
	 *
	 * @return void
	 */
	public function test_get_the_title_delegates_to_wordpress(): void {
		$model = new PostModel( array( 'ID' => 7 ) );

		Functions\expect( 'get_the_title' )
			->once()
			->with( 7 )
			->andReturn( 'Concert' );

		$this->assertSame( 'Concert', $model->get_the_title() );
	}

	/**
	 * It finds posts through PostDB and PostFilter.
	 *
	 * @return void
	 */
	public function test_find_by_id_uses_post_db_filter(): void {
		global $wpdb;

		$wpdb = $this->makePostLookupWpdb( $this->makePostRow( 21, 'post', 'DB post' ) );

		$model = PostModel::find_by_id( 21 );

		$this->assertInstanceOf( PostModel::class, $model );
		$this->assertSame( 21, $model->get_id() );
		$this->assertSame( 'DB post', $model->post_title );
		$this->assertStringContainsString( "AND p.post_type = 'post'", $wpdb->last_query );
		$this->assertStringContainsString( 'AND p.ID = 21', $wpdb->last_query );
	}

	/**
	 * It inserts a new post and refreshes generated fields.
	 *
	 * @return void
	 */
	public function test_save_inserts_post_and_refreshes(): void {
		$model             = new PostModel();
		$model->post_type  = 'tp_event';
		$model->post_title = 'Before save';

		Functions\expect( 'wp_insert_post' )
			->once()
			->andReturnUsing(
				function ( $data, $wp_error ) {
					$this->assertTrue( $wp_error );
					$this->assertSame( 'tp_event', $data['post_type'] );
					$this->assertSame( 'Before save', $data['post_title'] );

					return 99;
				}
			);

		Functions\expect( 'get_post' )
			->once()
			->with( 99 )
			->andReturn( $this->makePost( 99, 'tp_event', 'After save' ) );

		$model->save( true );

		$this->assertSame( 99, $model->get_id() );
		$this->assertSame( 'After save', $model->post_title );
	}
}
