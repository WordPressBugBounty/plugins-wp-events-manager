<?php
/**
 * Generic database query helpers.
 *
 * @package WPEMS/Databases
 */

namespace WPEMS\Databases;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class DataBase
 */
class DataBase {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * WordPress database object.
	 *
	 * @var object
	 */
	public $wpdb;

	/**
	 * WP core table names.
	 *
	 * @var string
	 */
	public $tb_users;

	/**
	 * WP posts table.
	 *
	 * @var string
	 */
	public $tb_posts;

	/**
	 * WP postmeta table.
	 *
	 * @var string
	 */
	public $tb_postmeta;

	/**
	 * WP options table.
	 *
	 * @var string
	 */
	public $tb_options;

	/**
	 * WP terms table.
	 *
	 * @var string
	 */
	public $tb_terms;

	/**
	 * WP term relationships table.
	 *
	 * @var string
	 */
	public $tb_term_relationships;

	/**
	 * WP term taxonomy table.
	 *
	 * @var string
	 */
	public $tb_term_taxonomy;

	/**
	 * Table collate clause.
	 *
	 * @var string
	 */
	private $collate = '';

	/**
	 * Maximum index length.
	 *
	 * @var string
	 */
	public $max_index_length = '191';

	/**
	 * Constructor.
	 */
	protected function __construct() {
		global $wpdb;

		$this->wpdb                  = $wpdb;
		$this->tb_users              = $wpdb->users;
		$this->tb_posts              = $wpdb->posts;
		$this->tb_postmeta           = $wpdb->postmeta;
		$this->tb_options            = $wpdb->options;
		$this->tb_terms              = $wpdb->terms;
		$this->tb_term_relationships = $wpdb->term_relationships;
		$this->tb_term_taxonomy      = $wpdb->term_taxonomy;

		$this->wpdb->hide_errors();
		$this->set_collate();
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
	 * Set table collate string from wpdb.
	 *
	 * @return void
	 */
	public function set_collate() {
		$collate = '';

		if ( $this->wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $this->wpdb->charset ) ) {
				$collate .= 'DEFAULT CHARACTER SET ' . $this->wpdb->charset;
			}

			if ( ! empty( $this->wpdb->collate ) ) {
				$collate .= ' COLLATE ' . $this->wpdb->collate;
			}
		}

		$this->collate = $collate;
	}

	/**
	 * Get table collate string.
	 *
	 * @return string
	 */
	public function get_collate(): string {
		return $this->collate;
	}

	/**
	 * Check whether a table exists.
	 *
	 * @param string $name_table Table name.
	 *
	 * @return bool|int
	 */
	public function check_table_exists( string $name_table ) {
		return $this->wpdb->query( $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $name_table ) );
	}

	/**
	 * Check whether a column exists.
	 *
	 * @param string $name_table Table name.
	 * @param string $name_col   Column name.
	 *
	 * @return bool|int
	 */
	public function check_col_table( string $name_table = '', string $name_col = '' ) {
		return $this->wpdb->query( $this->wpdb->prepare( "SHOW COLUMNS FROM $name_table LIKE %s", $name_col ) );
	}

	/**
	 * Clone a table into a backup table with `_bk` suffix.
	 *
	 * @param string $name_table Table name.
	 *
	 * @return bool
	 * @throws Exception If current user cannot manage options or the query fails.
	 */
	public function clone_table( string $name_table ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new Exception( esc_html__( 'You do not have permission.', 'wp-events-manager' ) );
		}

		$table_bk = $name_table . '_bk';
		$this->drop_table( $table_bk );

		$this->wpdb->query( "CREATE TABLE $table_bk LIKE $name_table" );
		$this->wpdb->query( "INSERT INTO $table_bk SELECT * FROM $name_table" );
		$this->check_execute_has_error();

		return true;
	}

	/**
	 * Drop a table.
	 *
	 * @param string $name_table Table name.
	 *
	 * @return bool|int
	 * @throws Exception If current user cannot manage options or the query fails.
	 */
	public function drop_table( string $name_table = '' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new Exception( esc_html__( 'You do not have permission.', 'wp-events-manager' ) );
		}

		if ( $this->check_table_exists( $name_table ) ) {
			$execute = $this->wpdb->query( "DROP TABLE $name_table" );
			$this->check_execute_has_error();

			return $execute;
		}

		return true;
	}

	/**
	 * Get list column names of a table.
	 *
	 * @param string $name_table Table name.
	 *
	 * @return array
	 * @throws Exception If query fails.
	 */
	public function get_cols_of_table( string $name_table ): array {
		$result = $this->wpdb->get_col( "SHOW COLUMNS FROM $name_table" );
		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Check current wpdb error state.
	 *
	 * @return void
	 * @throws Exception If wpdb has a last error.
	 */
	public function check_execute_has_error() {
		if ( ! empty( $this->wpdb->last_error ) ) {
			throw new Exception( $this->wpdb->last_error );
		}
	}

	/**
	 * Get total pages.
	 *
	 * @param int $limit      Page size.
	 * @param int $total_rows Total rows.
	 *
	 * @return int
	 */
	public static function get_total_pages( int $limit = 0, int $total_rows = 0 ): int {
		if ( 0 === $limit ) {
			return 0;
		}

		return (int) ceil( $total_rows / $limit );
	}

	/**
	 * Configure a filter to return a single-row query string.
	 *
	 * @param object $filter Filter object.
	 *
	 * @return void
	 */
	public function get_query_single_row( &$filter ) {
		$filter->limit               = 1;
		$filter->return_string_query = true;
		$filter->run_query_count     = false;
	}

	/**
	 * Build and execute a SELECT query.
	 *
	 * @param object $filter     Query filter.
	 * @param int    $total_rows Total rows.
	 *
	 * @return array|object|null|int|string
	 * @throws Exception If query fails.
	 */
	public function execute( $filter, int &$total_rows = 0 ) {
		$result = null;
		$where  = array( 'WHERE 1=1' );

		$fields = '*';
		if ( ! empty( $filter->only_fields ) ) {
			$fields = implode( ',', array_unique( $filter->only_fields ) );
		} elseif ( ! empty( $filter->fields ) ) {
			if ( ! empty( $filter->exclude_fields ) ) {
				foreach ( $filter->exclude_fields as $field ) {
					$index_field = array_search( $field, $filter->fields, true );
					if ( false !== $index_field ) {
						unset( $filter->fields[ $index_field ] );
					}
				}
			}

			foreach ( $filter->fields as $key => $field ) {
				if ( 'order' === $field ) {
					$filter->fields[ $key ] = '`order`';
				}
			}

			$fields = implode( ',', array_unique( $filter->fields ) );
		}
		$fields = apply_filters( 'wpems/query/fields', $fields, $filter );

		$inner_join = array_merge( array(), $filter->join );
		$inner_join = apply_filters( 'wpems/query/inner_join', $inner_join, $filter );
		$inner_join = implode( ' ', array_unique( $inner_join ) );

		$where = array_merge( $where, $filter->where );
		$where = apply_filters( 'wpems/query/where', $where, $filter );
		$where = implode( ' ', array_unique( $where ) );

		$group_by = '';
		if ( ! empty( $filter->group_by ) ) {
			$group_by = 'GROUP BY ' . $filter->group_by;
			$group_by = apply_filters( 'wpems/query/group_by', $group_by, $filter );
		}

		$order_by = '';
		if ( ! empty( $filter->order_by ) ) {
			$filter->order = strtoupper( $filter->order );
			if ( ! in_array( $filter->order, array( 'DESC', 'ASC' ), true ) ) {
				$filter->order = 'DESC';
			}

			$order_by = 'ORDER BY ' . $filter->order_by . ' ' . $filter->order . ' ';
			$order_by = apply_filters( 'wpems/query/order_by', $order_by, $filter );
		}

		$limit = '';
		if ( -1 !== (int) $filter->limit ) {
			$filter->limit = absint( $filter->limit );
			$offset        = ! empty( $filter->offset ) ? absint( $filter->offset ) : $filter->limit * ( absint( $filter->page ) - 1 );
			$limit         = $this->wpdb->prepare( 'LIMIT %d, %d', $offset, $filter->limit );
		}

		if ( $filter->return_string_query ) {
			$limit = '';
		}

		$collection       = ! empty( $filter->collection ) ? $filter->collection : '';
		$alias_collection = ! empty( $filter->collection_alias ) ? $filter->collection_alias : 'X';
		$query            = "SELECT $fields FROM $collection AS $alias_collection
		$inner_join
		$where
		$group_by
		$order_by
		$limit
		";

		if ( $filter->return_string_query ) {
			return $query;
		} elseif ( ! empty( $filter->union ) ) {
			$query  = implode( ' UNION ', array_unique( $filter->union ) );
			$query .= $group_by;
			$query .= $order_by;
			$query .= $limit;
		}

		if ( ! $filter->query_count ) {
			if ( $filter->debug_string_query ) {
				return $query;
			}

			$result = $this->wpdb->get_results( $query );
		}

		if ( $filter->run_query_count ) {
			$query       = str_replace( array( $limit, $order_by ), '', $query );
			$query_total = "SELECT COUNT($filter->field_count) FROM ($query) AS $alias_collection";
			$total_rows  = (int) $this->wpdb->get_var( $query_total );
			$this->check_execute_has_error();

			if ( $filter->query_count ) {
				if ( $filter->debug_string_query ) {
					return $query_total;
				}

				return $total_rows;
			}
		}

		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Execute an UPDATE query from a filter.
	 *
	 * @param object $filter Query filter.
	 *
	 * @return bool|int
	 * @throws Exception If query fails.
	 */
	public function update_execute( $filter ) {
		$set = apply_filters( 'wpems/query/update/set', $filter->set, $filter );
		$set = implode( ',', array_unique( $set ) );

		$where = array( 'WHERE 1=1' );
		$where = array_merge( $where, $filter->where );
		$where = apply_filters( 'wpems/query/update/where', $where, $filter );
		$where = implode( ' ', array_unique( $where ) );

		$query  = "UPDATE $filter->collection";
		$query .= " SET $set $where";
		$result = $this->wpdb->query( $query );
		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Execute a DELETE query from a filter.
	 *
	 * @param object $filter Query filter.
	 * @param string $table  Optional DELETE table alias.
	 *
	 * @return bool|int|string|null
	 * @throws Exception If query fails.
	 */
	public function delete_execute( $filter, string $table = '' ) {
		$where = array( 'WHERE 1=1' );
		$where = array_merge( $where, $filter->where );
		$where = apply_filters( 'wpems/query/delete/where', $where, $filter );
		$where = implode( ' ', array_unique( $where ) );

		$inner_join = array_merge( array(), $filter->join );
		$inner_join = apply_filters( 'wpems/query/delete/inner_join', $inner_join, $filter );
		$inner_join = implode( ' ', array_unique( $inner_join ) );

		$query = "DELETE $table FROM $filter->collection $inner_join $where";
		if ( $filter->return_string_query ) {
			return $query;
		}

		$result = $this->wpdb->query( $query );
		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Get values from a list of objects by key.
	 *
	 * @param array  $arr_object Object list.
	 * @param string $key        Object key.
	 *
	 * @return array
	 */
	public static function get_values_by_key( array $arr_object, string $key = 'ID' ): array {
		$values = array();
		foreach ( $arr_object as $object ) {
			$values[] = $object->{$key};
		}

		return $values;
	}

	/**
	 * Insert whitelisted data into a table.
	 *
	 * @param array $args Insert args.
	 *
	 * @return int
	 * @throws Exception If insert fails.
	 */
	public function insert_data( array $args ): int {
		$data               = $args['data'] ?? array();
		$filter             = $args['filter'] ?? null;
		$table_name         = $args['table_name'] ?? '';
		$key_auto_increment = sanitize_key( $args['key_auto_increment'] ?? '' );

		if ( empty( $data ) || ! is_array( $data ) ) {
			throw new Exception( esc_html__( 'Data must be an array.', 'wp-events-manager' ) );
		}

		if ( empty( $filter->all_fields ) ) {
			throw new Exception( esc_html__( 'Filter must define all fields.', 'wp-events-manager' ) );
		}

		if ( empty( $table_name ) ) {
			throw new Exception( esc_html__( 'Table name is required.', 'wp-events-manager' ) );
		}

		if ( empty( $key_auto_increment ) ) {
			throw new Exception( esc_html__( 'Key auto increment is required.', 'wp-events-manager' ) );
		}

		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields, true ) ) {
				unset( $data[ $col_name ] );
			}
		}

		unset( $data[ $key_auto_increment ] );

		$this->wpdb->insert( $table_name, $data );
		$this->check_execute_has_error();

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Update whitelisted data in a table.
	 *
	 * @param array $args Update args.
	 *
	 * @return bool
	 * @throws Exception If update fails.
	 */
	public function update_data( array $args ): bool {
		$data       = $args['data'] ?? array();
		$filter     = $args['filter'] ?? null;
		$table_name = $args['table_name'] ?? '';
		$where_key  = sanitize_key( $args['where_key'] ?? '' );

		if ( empty( $filter->all_fields ) ) {
			throw new Exception( esc_html__( 'Filter must define all fields.', 'wp-events-manager' ) );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			throw new Exception( esc_html__( 'Data must be an array.', 'wp-events-manager' ) );
		}

		if ( empty( $where_key ) || empty( $table_name ) ) {
			throw new Exception( esc_html__( 'Invalid update arguments.', 'wp-events-manager' ) );
		}

		$filter->collection = $table_name;
		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields, true ) ) {
				continue;
			}

			if ( 'order' === $col_name ) {
				$col_name = '`order`';
			}

			if ( is_null( $value ) ) {
				$filter->set[] = $col_name . ' = null';
			} else {
				$filter->set[] = $this->wpdb->prepare( $col_name . ' = %s', $value );
			}
		}

		$filter->where[] = $this->wpdb->prepare( "AND $where_key = %d", $data[ $where_key ] );
		$this->update_execute( $filter );

		return true;
	}
}
