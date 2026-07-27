<?php
/**
 * Event database tests.
 *
 * @package WPEMS\Tests\Unit\Databases
 */

namespace WPEMS\Tests\Unit\Databases;

use WPEMS\Databases\EventDB;
use WPEMS\Filters\EventFilter;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test event database query wrapper.
 */
class EventDBTest extends TestCase {

	/**
	 * Reset singleton.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( EventDB::class, 'instance', null );
	}

	/**
	 * It returns zero for invalid event IDs.
	 *
	 * @return void
	 */
	public function test_get_booked_quantity_returns_zero_for_empty_event_id(): void {
		global $wpdb;

		$wpdb  = new EventDBWpdbFake();
		$filter = new EventFilter();

		$this->assertSame( 0, EventDB::getInstance()->get_booked_quantity( $filter ) );
	}

	/**
	 * It returns booked quantity from wpdb.
	 *
	 * @return void
	 */
	public function test_get_booked_quantity_returns_int(): void {
		global $wpdb;

		$wpdb  = new EventDBWpdbFake( array( (object) array( 'qty' => '4' ) ) );
		$filter = new EventFilter();
		$filter->event_id = 22;

		$this->assertSame( 4, EventDB::getInstance()->get_booked_quantity( $filter ) );
		$this->assertStringContainsString( 'SUM( pm.meta_value ) AS qty', $wpdb->last_results_query );
		$this->assertStringContainsString( 'AND event.ID = 22', $wpdb->last_results_query );
	}

	/**
	 * It omits sorting from the aggregate booked quantity query.
	 *
	 * @return void
	 */
	public function test_get_booked_quantity_does_not_order_aggregate_query(): void {
		global $wpdb;

		$wpdb  = new EventDBWpdbFake( array( (object) array( 'qty' => '4' ) ) );
		$filter = new EventFilter();
		$filter->event_id = 22;

		EventDB::getInstance()->get_booked_quantity( $filter );

		$this->assertStringNotContainsString( 'ORDER BY', $wpdb->last_results_query );
		$this->assertStringNotContainsString( 'post_date', $wpdb->last_results_query );
	}

	/**
	 * It includes user ID when requested.
	 *
	 * @return void
	 */
	public function test_get_booked_quantity_accepts_user_filter(): void {
		global $wpdb;

		$wpdb  = new EventDBWpdbFake( array( (object) array( 'qty' => '2' ) ) );
		$filter = new EventFilter();
		$filter->event_id = 22;
		$filter->user_id  = 9;

		$this->assertSame( 2, EventDB::getInstance()->get_booked_quantity( $filter ) );
		$this->assertStringContainsString( 'AND user.ID = 9', $wpdb->last_results_query );
		$this->assertStringNotContainsString( "AND book.post_status = 'ea-completed'", $wpdb->last_results_query );
	}

	/**
	 * It returns registered booking rows.
	 *
	 * @return void
	 */
	public function test_get_registered_bookings_returns_array(): void {
		global $wpdb;

		$rows = array(
			(object) array( 'ID' => 300 ),
			(object) array( 'ID' => 301 ),
		);

		$wpdb  = new EventDBWpdbFake( $rows, array( '2' ) );
		$filter = new EventFilter();
		$filter->event_id = 22;
		$total_rows = 0;

		$this->assertSame( $rows, EventDB::getInstance()->get_registered_bookings( $filter, $total_rows ) );
		$this->assertSame( 2, $total_rows );
	}
}

/**
 * wpdb fake for EventDB tests.
 */
class EventDBWpdbFake {
	/**
	 * Core table names and charset fields.
	 *
	 * @var string
	 */
	public $users = 'wp_users', $posts = 'wp_posts', $postmeta = 'wp_postmeta', $options = 'wp_options', $terms = 'wp_terms', $term_relationships = 'wp_term_relationships', $term_taxonomy = 'wp_term_taxonomy', $charset = 'utf8mb4', $collate = 'utf8mb4_unicode_ci', $last_error = '', $last_results_query = '', $last_var_query = '';

	/**
	 * Rows returned by get_results().
	 *
	 * @var array
	 */
	private $results;

	/**
	 * Values returned by get_var().
	 *
	 * @var array
	 */
	private $var_results;

	/**
	 * Constructor.
	 *
	 * @param array $results     Rows returned by get_results().
	 * @param array $var_results Values returned by get_var().
	 */
	public function __construct( array $results = array(), array $var_results = array() ) {
		$this->results     = $results;
		$this->var_results = $var_results;
	}

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

	/**
	 * Get result rows.
	 *
	 * @param string $query SQL query.
	 *
	 * @return array
	 */
	public function get_results( string $query ): array {
		$this->last_results_query = $query;

		return $this->results;
	}

	/**
	 * Get a scalar value.
	 *
	 * @param string $query SQL query.
	 *
	 * @return mixed
	 */
	public function get_var( string $query ) {
		$this->last_var_query = $query;

		if ( empty( $this->var_results ) ) {
			return '0';
		}

		return array_shift( $this->var_results );
	}
}
