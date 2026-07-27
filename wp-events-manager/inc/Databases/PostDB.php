<?php
/**
 * WordPress post database queries.
 *
 * @package WPEMS/Databases
 */

namespace WPEMS\Databases;

use WPEMS\Filters\PostFilter;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostDB
 */
class PostDB extends DataBase {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get posts by filter.
	 *
	 * @param PostFilter $filter     Post filter.
	 * @param int        $total_rows Total rows.
	 *
	 * @return array|null|int|string
	 * @throws \Exception If query fails.
	 */
	public function get_posts( PostFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'p';
		}

		$ca = $filter->collection_alias;

		$filter->where[] = $this->wpdb->prepare( "AND $ca.post_type = %s", $filter->post_type );

		if ( ! empty( $filter->ID ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID = %d", $filter->ID );
		}

		$filter->post_status = (array) $filter->post_status;
		if ( ! empty( $filter->post_status ) ) {
			$post_status_format = self::db_format_array( $filter->post_status, '%s' );
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholder list is generated from the sanitized status array size.
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_status IN ($post_status_format)", $filter->post_status );
		}

		if ( ! empty( $filter->term_ids ) ) {
			$filter->term_ids = array_map( 'absint', $filter->term_ids );
			$term_ids_format  = implode( ',', $filter->term_ids );
			$filter->join[]   = "INNER JOIN $this->tb_term_relationships AS r_term_p ON $ca.ID = r_term_p.object_id";
			$filter->join[]   = "INNER JOIN $this->tb_term_taxonomy AS tx_p ON r_term_p.term_taxonomy_id = tx_p.term_taxonomy_id";
			$filter->where[]  = "AND r_term_p.term_taxonomy_id IN ($term_ids_format)";
			$filter->where[]  = $this->wpdb->prepare( 'AND tx_p.taxonomy = %s', $filter->taxonomy );
		}

		if ( ! empty( $filter->post_ids ) ) {
			$post_ids        = array_map( 'absint', $filter->post_ids );
			$post_ids_format = implode( ',', $post_ids );
			$filter->where[] = "AND $ca.ID IN ($post_ids_format)";
		}

		if ( ! empty( $filter->post_title ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_title LIKE %s", '%' . $filter->post_title . '%' );
		}

		if ( ! empty( $filter->post_name ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_name = %s", $filter->post_name );
		}

		if ( isset( $filter->post_author ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_author = %d", $filter->post_author );
		}

		if ( ! empty( $filter->post_authors ) ) {
			$post_authors        = array_map( 'absint', $filter->post_authors );
			$post_authors_format = implode( ',', $post_authors );
			$filter->where[]     = "AND $ca.post_author IN ($post_authors_format)";
		}

		$filter = apply_filters( 'wpems/post/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Build a placeholder list for a prepared IN() clause.
	 *
	 * @param array  $arr    Values.
	 * @param string $format Placeholder format.
	 *
	 * @return string
	 */
	public static function db_format_array( array $arr, string $format = '%d' ): string {
		return implode( ',', array_fill( 0, count( $arr ), $format ) );
	}
}
