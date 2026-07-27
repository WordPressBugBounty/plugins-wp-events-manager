<?php
/**
 * Post database tests.
 *
 * @package WPEMS\Tests\Unit\Databases
 */

namespace WPEMS\Tests\Unit\Databases;

use WPEMS\Databases\PostDB;
use WPEMS\Filters\EventFilter;
use WPEMS\Filters\PostFilter;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test post database query behavior.
 */
class PostDBTest extends TestCase {

	/**
	 * Reset singleton.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( PostDB::class, 'instance', null );
	}

	/**
	 * It builds a single-row query string from a post filter.
	 *
	 * @return void
	 */
	public function test_get_posts_can_return_single_row_query_string(): void {
		global $wpdb;

		$wpdb = new PostDBWpdbFake();

		$db                  = PostDB::getInstance();
		$filter              = new EventFilter();
		$filter->ID          = 22;
		$total_rows          = 0;
		$filter->only_fields = array( PostFilter::COL_ID );

		$db->get_query_single_row( $filter );
		$query = $db->get_posts( $filter, $total_rows );

		$this->assertIsString( $query );
		$this->assertStringContainsString( 'SELECT ID FROM wp_posts AS p', $query );
		$this->assertStringContainsString( "AND p.post_type = 'tp_event'", $query );
		$this->assertStringContainsString( 'AND p.ID = 22', $query );
		$this->assertStringNotContainsString( 'LIMIT', $query );
		$this->assertSame( 0, $total_rows );
	}

	/**
	 * It formats placeholders for prepared IN() clauses.
	 *
	 * @return void
	 */
	public function test_db_format_array(): void {
		$this->assertSame( '%s,%s,%s', PostDB::db_format_array( array( 'a', 'b', 'c' ), '%s' ) );
		$this->assertSame( '%d,%d', PostDB::db_format_array( array( 1, 2 ) ) );
	}
}

/**
 * wpdb fake for PostDB tests.
 */
class PostDBWpdbFake {
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

	/**
	 * Prepare a query for test assertions.
	 *
	 * @param string $query Query with placeholders.
	 * @param mixed  ...$args Placeholder values.
	 *
	 * @return string
	 */
	public function prepare( string $query, ...$args ): string {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$index = 0;

		return preg_replace_callback(
			'/%[sd]/',
			function ( $matches ) use ( $args, &$index ) {
				$value = $args[ $index++ ] ?? '';

				if ( '%d' === $matches[0] ) {
					return (string) (int) $value;
				}

				return "'" . str_replace( "'", "''", (string) $value ) . "'";
			},
			$query
		);
	}
}
