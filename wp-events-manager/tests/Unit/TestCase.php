<?php
/**
 * Shared unit test base.
 *
 * @package WPEMS\Tests\Unit
 */

namespace WPEMS\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use WP_Post;

/**
 * Base test case with Brain Monkey lifecycle.
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Set up common WordPress function doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'wp_unslash' )->alias(
			function ( $value ) {
				if ( is_array( $value ) ) {
					return array_map( 'stripslashes', $value );
				}

				return stripslashes( (string) $value );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			function ( $value ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
			}
		);
		Functions\when( 'sanitize_email' )->alias(
			function ( $value ) {
				return filter_var( (string) $value, FILTER_SANITIZE_EMAIL );
			}
		);
		Functions\when( 'sanitize_user' )->alias(
			function ( $value ) {
				return preg_replace( '/[^A-Za-z0-9_\-.@]/', '', (string) $value );
			}
		);
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'maybe_unserialize' )->returnArg( 1 );
		Functions\when( 'is_wp_error' )->alias(
			function ( $value ) {
				return $value instanceof \WP_Error;
			}
		);
		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults = array() ) {
				return array_merge( (array) $defaults, (array) $args );
			}
		);
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			function ( $tag, $value = null ) {
				return $value;
			}
		);
	}

	/**
	 * Tear down Brain Monkey and Mockery.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Build a WP_Post double.
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type Post type.
	 * @param string $title     Post title.
	 * @param string $status    Post status.
	 *
	 * @return WP_Post
	 */
	protected function makePost( int $id, string $post_type = 'post', string $title = 'Test post', string $status = 'publish' ): WP_Post {
		return new WP_Post(
			array(
				'ID'                => $id,
				'post_author'       => 1,
				'post_date'         => '2026-01-01 00:00:00',
				'post_date_gmt'     => '2026-01-01 00:00:00',
				'post_modified'     => '2026-01-01 00:00:00',
				'post_modified_gmt' => '2026-01-01 00:00:00',
				'post_content'      => 'Content',
				'post_title'        => $title,
				'post_excerpt'      => 'Excerpt',
				'post_status'       => $status,
				'post_password'     => '',
				'post_name'         => 'test-post',
				'post_type'         => $post_type,
				'post_parent'       => 0,
				'filter'            => 'raw',
			)
		);
	}

	/**
	 * Build a database row from a WP_Post double.
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type Post type.
	 * @param string $title     Post title.
	 * @param string $status    Post status.
	 *
	 * @return \stdClass
	 */
	protected function makePostRow( int $id, string $post_type = 'post', string $title = 'Test post', string $status = 'publish' ): \stdClass {
		return (object) get_object_vars( $this->makePost( $id, $post_type, $title, $status ) );
	}

	/**
	 * Build a wpdb fake for PostDB-backed model lookups.
	 *
	 * @param \stdClass|array|null $row         Row returned by get_row(), or rows keyed by post ID.
	 * @param array                $var_results Values returned by get_var().
	 *
	 * @return object
	 */
	protected function makePostLookupWpdb( $row, array $var_results = array() ) {
		return new class( $row, $var_results ) {
			/**
			 * Core table names and charset fields.
			 *
			 * @var string
			 */
			public $users = 'wp_users', $posts = 'wp_posts', $postmeta = 'wp_postmeta', $options = 'wp_options', $terms = 'wp_terms', $term_relationships = 'wp_term_relationships', $term_taxonomy = 'wp_term_taxonomy', $charset = 'utf8mb4', $collate = 'utf8mb4_unicode_ci', $last_error = '', $last_query = '';

			/**
			 * Rows returned by get_row().
			 *
			 * @var array
			 */
			private $rows;

			/**
			 * Whether rows are keyed by post ID.
			 *
			 * @var bool
			 */
			private $is_map;

			/**
			 * Values returned by get_var().
			 *
			 * @var array
			 */
			private $var_results;

			/**
			 * Constructor.
			 *
			 * @param \stdClass|array|null $row         Row returned by get_row(), or rows keyed by post ID.
			 * @param array                $var_results Values returned by get_var().
			 */
			public function __construct( $row, array $var_results ) {
				$this->is_map      = is_array( $row );
				$this->rows        = $this->is_map ? $row : array( $row );
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
			 * Get a single row.
			 *
			 * @param string $query SQL query.
			 *
			 * @return \stdClass|null
			 */
			public function get_row( string $query ) {
				$this->last_query = $query;

				foreach ( $this->rows as $id => $row ) {
					if ( is_numeric( $id ) && false !== strpos( $query, 'p.ID = ' . (int) $id ) ) {
						return $row;
					}
				}

				if ( $this->is_map ) {
					return null;
				}

				return reset( $this->rows );
			}

			/**
			 * Get result rows.
			 *
			 * @param string $query SQL query.
			 *
			 * @return array
			 */
			public function get_results( string $query ): array {
				$this->last_query = $query;

				return array( (object) array( 'qty' => $this->get_var( $query ) ) );
			}

			/**
			 * Get a scalar value.
			 *
			 * @param string $query SQL query.
			 *
			 * @return mixed
			 */
			public function get_var( string $query ) {
				$this->last_query = $query;

				if ( empty( $this->var_results ) ) {
					return '0';
				}

				return array_shift( $this->var_results );
			}
		};
	}

	/**
	 * Reset a private static class property.
	 *
	 * @param string $class    Class name.
	 * @param string $property Property name.
	 * @param mixed  $value    New value.
	 *
	 * @return void
	 */
	protected function resetStaticProperty( string $class, string $property, $value ): void {
		$reflection = new ReflectionClass( $class );
		$prop       = $reflection->getProperty( $property );
		$prop->setAccessible( true );
		$prop->setValue( null, $value );
	}
}
