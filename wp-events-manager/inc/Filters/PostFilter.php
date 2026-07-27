<?php
/**
 * WordPress post query filter.
 *
 * @package WPEMS/Filters
 */

namespace WPEMS\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Class PostFilter
 */
class PostFilter extends FilterBase {
	const COL_ID                    = 'ID';
	const COL_POST_AUTHOR           = 'post_author';
	const COL_POST_DATE             = 'post_date';
	const COL_POST_DATE_GMT         = 'post_date_gmt';
	const COL_POST_CONTENT          = 'post_content';
	const COL_POST_TITLE            = 'post_title';
	const COL_POST_EXCERPT          = 'post_excerpt';
	const COL_POST_STATUS           = 'post_status';
	const COL_COMMENT_STATUS        = 'comment_status';
	const COL_PING_STATUS           = 'ping_status';
	const COL_POST_PASSWORD         = 'post_password';
	const COL_POST_NAME             = 'post_name';
	const COL_TO_PING               = 'to_ping';
	const COL_PINGED                = 'pinged';
	const COL_POST_MODIFIED         = 'post_modified';
	const COL_POST_MODIFIED_GMT     = 'post_modified_gmt';
	const COL_POST_CONTENT_FILTERED = 'post_content_filtered';
	const COL_POST_PARENT           = 'post_parent';
	const COL_GUID                  = 'guid';
	const COL_MENU_ORDER            = 'menu_order';
	const COL_POST_TYPE             = 'post_type';
	const COL_POST_MIME_TYPE        = 'post_mime_type';
	const COL_COMMENT_COUNT         = 'comment_count';

	/**
	 * All supported wp_posts fields.
	 *
	 * @var string[]
	 */
	public $all_fields = array(
		self::COL_ID,
		self::COL_POST_AUTHOR,
		self::COL_POST_DATE,
		self::COL_POST_DATE_GMT,
		self::COL_POST_CONTENT,
		self::COL_POST_TITLE,
		self::COL_POST_EXCERPT,
		self::COL_POST_STATUS,
		self::COL_COMMENT_STATUS,
		self::COL_PING_STATUS,
		self::COL_POST_PASSWORD,
		self::COL_POST_NAME,
		self::COL_TO_PING,
		self::COL_PINGED,
		self::COL_POST_MODIFIED,
		self::COL_POST_MODIFIED_GMT,
		self::COL_POST_CONTENT_FILTERED,
		self::COL_POST_PARENT,
		self::COL_GUID,
		self::COL_MENU_ORDER,
		self::COL_POST_TYPE,
		self::COL_POST_MIME_TYPE,
		self::COL_COMMENT_COUNT,
	);

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * Post title search.
	 *
	 * @var string
	 */
	public $post_title = '';

	/**
	 * Post slug.
	 *
	 * @var string
	 */
	public $post_name = '';

	/**
	 * Post statuses.
	 *
	 * @var string[]
	 */
	public $post_status = array();

	/**
	 * Single author ID.
	 *
	 * @var int|null
	 */
	public $post_author;

	/**
	 * Multiple author IDs.
	 *
	 * @var int[]
	 */
	public $post_authors = array();

	/**
	 * Term taxonomy IDs.
	 *
	 * @var int[]
	 */
	public $term_ids = array();

	/**
	 * Tag IDs.
	 *
	 * @var int[]
	 */
	public $tag_ids = array();

	/**
	 * Post IDs.
	 *
	 * @var int[]
	 */
	public $post_ids = array();

	/**
	 * Taxonomy name used with term IDs.
	 *
	 * @var string
	 */
	public $taxonomy = 'category';
}
