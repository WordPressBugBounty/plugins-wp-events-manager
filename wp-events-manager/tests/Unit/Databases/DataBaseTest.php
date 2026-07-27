<?php
/**
 * Database base tests.
 *
 * @package WPEMS\Tests\Unit\Databases
 */

namespace WPEMS\Tests\Unit\Databases;

use WPEMS\Databases\DataBase;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test database foundation behavior.
 */
class DataBaseTest extends TestCase {

	/**
	 * Reset singleton.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( DataBase::class, 'instance', null );
	}

	/**
	 * It declares WP core table names used by WPEMS queries.
	 *
	 * @return void
	 */
	public function test_declares_wp_core_table_names(): void {
		global $wpdb;

		$wpdb = new DataBaseWpdbFake();

		$db = DataBase::getInstance();

		$this->assertSame( 'wp_users', $db->tb_users );
		$this->assertSame( 'wp_posts', $db->tb_posts );
		$this->assertSame( 'wp_postmeta', $db->tb_postmeta );
		$this->assertSame( 'wp_options', $db->tb_options );
		$this->assertSame( 'wp_terms', $db->tb_terms );
		$this->assertSame( 'wp_term_relationships', $db->tb_term_relationships );
		$this->assertSame( 'wp_term_taxonomy', $db->tb_term_taxonomy );
		$this->assertSame( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $db->get_collate() );
	}

	/**
	 * It calculates total pages.
	 *
	 * @return void
	 */
	public function test_get_total_pages(): void {
		$this->assertSame( 0, DataBase::get_total_pages( 0, 25 ) );
		$this->assertSame( 3, DataBase::get_total_pages( 10, 25 ) );
	}
}

/**
 * wpdb fake for DataBase tests.
 */
class DataBaseWpdbFake {
	/**
	 * Core table names and charset fields.
	 *
	 * @var string
	 */
	public $users = 'wp_users', $posts = 'wp_posts', $postmeta = 'wp_postmeta', $options = 'wp_options', $terms = 'wp_terms', $term_relationships = 'wp_term_relationships', $term_taxonomy = 'wp_term_taxonomy', $charset = 'utf8mb4', $collate = 'utf8mb4_unicode_ci', $last_error = '';

	/**
	 * Hide errors.
	 *
	 * @return void
	 */
	public function hide_errors(): void {}

	/**
	 * Check wpdb capability.
	 *
	 * @param string $cap Capability.
	 *
	 * @return bool
	 */
	public function has_cap( string $cap ): bool {
		return 'collation' === $cap;
	}
}
