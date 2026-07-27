<?php
/**
 * Base query filter data object.
 *
 * @package WPEMS/Filters
 */

namespace WPEMS\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Class FilterBase
 */
class FilterBase {
	const ORDER_DESC = 'DESC';
	const ORDER_ASC  = 'ASC';

	/**
	 * Query limit. Use -1 for no limit.
	 *
	 * @var int
	 */
	public $limit = 10;

	/**
	 * Maximum query limit.
	 *
	 * @var int
	 */
	public $max_limit = 100;

	/**
	 * Sort field map.
	 *
	 * @var array
	 */
	public $sort_by = array();

	/**
	 * Group by SQL fragment.
	 *
	 * @var string
	 */
	public $group_by = '';

	/**
	 * Order by field.
	 *
	 * @var string
	 */
	public $order_by = '';

	/**
	 * Sort direction.
	 *
	 * @var string
	 */
	public $order = '';

	/**
	 * Keyword search value.
	 *
	 * @var string
	 */
	public $key_word = '';

	/**
	 * Current page for limit offset calculation.
	 *
	 * @var int
	 */
	public $page = 1;

	/**
	 * Explicit offset. When greater than zero, it takes precedence over page.
	 *
	 * @var int
	 */
	public $offset = 0;

	/**
	 * Query collection or nested query.
	 *
	 * @var string
	 */
	public $collection = '';

	/**
	 * Collection alias.
	 *
	 * @var string
	 */
	public $collection_alias = '';

	/**
	 * Selectable fields.
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Fields to select exclusively.
	 *
	 * @var array
	 */
	public $only_fields = array();

	/**
	 * Fields excluded from the selectable field list.
	 *
	 * @var array
	 */
	public $exclude_fields = array();

	/**
	 * Update SET clauses.
	 *
	 * @var array
	 */
	public $set = array();

	/**
	 * WHERE clauses.
	 *
	 * @var array
	 */
	public $where = array();

	/**
	 * JOIN clauses.
	 *
	 * @var array
	 */
	public $join = array();

	/**
	 * UNION query clauses.
	 *
	 * @var array
	 */
	public $union = array();

	/**
	 * Whether to run the total count query.
	 *
	 * @var bool
	 */
	public $run_query_count = true;

	/**
	 * Whether to return only the total count.
	 *
	 * @var bool
	 */
	public $query_count = false;

	/**
	 * Field used in COUNT().
	 *
	 * @var string
	 */
	public $field_count = 'ID';

	/**
	 * Whether to return the built query string.
	 *
	 * @var bool
	 */
	public $return_string_query = false;

	/**
	 * Whether to return the query for debugging.
	 *
	 * @var bool
	 */
	public $debug_string_query = false;

	/**
	 * Expected wpdb query method.
	 *
	 * @var string
	 */
	public $query_type = 'get_results';

	/**
	 * Extra filter data.
	 *
	 * @var object|null
	 */
	public $filter_extra;
}
