<?php

namespace WPEMS\Databases;

use WPEMS\Filters\UserFilter;
use Exception;

defined( 'ABSPATH' ) || exit();

/**
 * Class UserDB
 *
 * @since 2.2.5
 * @version 1.0.0
 */
class UserDB extends DataBase {
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
	 * get users
	 *
	 * @param userfilter $filter
	 * @param int $total_rows
	 *
	 * @return array|object|null|int|string
	 * @throws exception
	 * @since 2.2.5
	 * @version 1.0.0
	 */
	public function get_users( userfilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_users;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'u';
		}

		$ca = $filter->collection_alias;

		// find id
		if ( isset( $filter->id ) ) {
			$filter->where[] = $this->wpdb->prepare( "and $ca.id = %d", $filter->id );
		}

		// find by ids
		if ( ! empty( $filter->ids ) ) {
			$ids_format      = implode( ', ', array_fill( 0, count( $filter->ids ), '%d' ) );
			$filter->where[] = $this->wpdb->prepare( "and $ca.id in ($ids_format)", $filter->ids );
		}

		// find by user_nicename
		if ( ! empty( $filter->user_nicename ) ) {
			$filter->where[] = $this->wpdb->prepare( "and $ca.user_nicename like %s", '%' . $filter->user_nicename . '%' );
		}

		// find by user_email
		if ( ! empty( $filter->user_email ) ) {
			$filter->where[] = $this->wpdb->prepare( "and $ca.user_email = %s", $filter->user_email );
		}

		// find by user_login
		if ( ! empty( $filter->user_login ) ) {
			$filter->where[] = $this->wpdb->prepare( "and $ca.user_login = %s", $filter->user_login );
		}

		// find by display_name
		if ( ! empty( $filter->display_name ) ) {
			$filter->where[] = $this->wpdb->prepare( "and $ca.display_name like %s", '%' . $filter->display_name . '%' );
		}

		$filter = apply_filters( 'wpems/user/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
