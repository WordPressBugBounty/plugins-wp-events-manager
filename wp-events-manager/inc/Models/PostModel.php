<?php
/**
 * Class PostModel
 *
 * Base model for WordPress post types.
 * Adapted from LearnPress\Models\PostModel — all LP-specific dependencies removed.
 *
 * @package    WPEMS/Models
 * @version    1.0.0
 * @since      2.3.0
 */

namespace WPEMS\Models;

use Exception;
use stdClass;
use Throwable;
use WPEMS\Databases\PostDB;
use WPEMS\Filters\PostFilter;
use WP_Post;
use WP_Error;
use WP_Term;

defined( 'ABSPATH' ) || exit();

class PostModel {

	/**
	 * Auto increment, Primary key.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * @var int
	 */
	public $post_author = 0;

	/**
	 * @var string|null
	 */
	public $post_date = null;

	/**
	 * @var string|null
	 */
	public $post_date_gmt = null;

	/**
	 * @var string|null
	 */
	public $post_modified = null;

	/**
	 * @var string|null
	 */
	public $post_modified_gmt = null;

	/**
	 * @var string
	 */
	public $post_content = '';

	/**
	 * @var string
	 */
	public $post_title = '';

	/**
	 * @var string
	 */
	public $post_excerpt = '';

	/**
	 * @var string
	 */
	public $post_status = '';

	/**
	 * @var string
	 */
	public $post_password = '';

	/**
	 * @var string
	 */
	public $post_name = '';

	/**
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * @var int
	 */
	public $post_parent = 0;

	/**
	 * All meta data.
	 *
	 * @var stdClass
	 */
	public $meta_data = null;

	/**
	 * Flag indicating if meta data has been loaded.
	 *
	 * @var bool|null
	 */
	public $is_got_meta_data;

	/**
	 * Only for compatibility with WP_Post.
	 *
	 * @var string
	 */
	public $filter;

	const STATUS_PUBLISH      = 'publish';
	const STATUS_TRASH        = 'trash';
	const STATUS_DRAFT        = 'draft';
	const STATUS_PRIVATE      = 'private';
	const STATUS_PENDING      = 'pending';
	const STATUS_FEATURE      = 'future';
	const STATUS_AUTO_DRAFT   = 'auto-draft';
	const VISIBILITY_PASSWORD = 'password';

	/**
	 * If data provided, map to object.
	 * Otherwise create new empty object.
	 *
	 * @param array|object|mixed $data Optional data to map.
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}

		if ( is_null( $this->meta_data ) ) {
			$this->meta_data = new stdClass();
		}
	}

	/**
	 * Get user model of the post author.
	 *
	 * @return false|UserModel
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_author_model() {
		if ( ! empty( $this->post_author ) ) {
			$author_id = $this->post_author;
		} else {
			$author_id = get_post_field( 'post_author', $this );
		}

		return UserModel::find( $author_id );
	}

	/**
	 * Map array or object data to this model.
	 *
	 * @param array|object|mixed $data Data from database or WP_Post.
	 *
	 * @return PostModel|static
	 */
	public function map_to_object( $data ): PostModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Find a post by ID.
	 *
	 * Validates that the post exists and matches the expected post_type
	 * of the calling class through PostDB.
	 *
	 * @param int  $post_id    The post ID.
	 * @param bool $check_type Retained for backwards compatibility.
	 *
	 * @return false|static Returns the model instance or false if not found.
	 */
	public static function find_by_id( int $post_id, bool $check_type = true ) {
		if ( $post_id <= 0 ) {
			return false;
		}

		$filter            = new PostFilter();
		$filter->ID        = $post_id;
		$filter->post_type = ( new static() )->post_type;

		return static::get_item_model_from_db( $filter );
	}

	/**
	 * Get a post model from database by filter.
	 *
	 * @param PostFilter $filter Post query filter.
	 *
	 * @return false|static Returns the model instance or false if not found.
	 */
	public static function get_item_model_from_db( PostFilter $filter ) {
		$post_db    = PostDB::getInstance();
		$post_model = false;

		try {
			if ( empty( $filter->post_type ) ) {
				$filter->post_type = ( new static() )->post_type;
			}

			$post_db->get_query_single_row( $filter );
			$query_single_row = $post_db->get_posts( $filter );
			$post_rs          = $post_db->wpdb->get_row( $query_single_row );

			if ( $post_rs instanceof stdClass ) {
				$post_model = new static( $post_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $post_model;
	}

	/**
	 * Get all meta data for this post.
	 *
	 * @return stdClass
	 */
	public function get_all_metadata(): stdClass {
		if ( ! isset( $this->is_got_meta_data ) ) {
			$raw_meta = get_post_meta( $this->get_id() );

			$this->meta_data = new stdClass();
			if ( is_array( $raw_meta ) ) {
				foreach ( $raw_meta as $key => $values ) {
					$this->meta_data->{$key} = isset( $values[0] )
						? maybe_unserialize( $values[0] )
						: '';
				}
			}

			$this->is_got_meta_data = true;
		}

		return $this->meta_data;
	}

	/**
	 * Check capabilities to create a new post.
	 *
	 * Override in child classes for custom permission logic.
	 *
	 * @return bool
	 */
	public function check_capabilities_create(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check capabilities to update a post.
	 *
	 * Override in child classes for custom permission logic.
	 *
	 * @return bool
	 */
	public function check_capabilities_update(): bool {
		return current_user_can( 'edit_post', $this->ID );
	}

	/**
	 * Save post to database.
	 *
	 * If ID is empty, inserts a new post. Otherwise updates.
	 *
	 * @param bool $force_save Skip capability checks when true.
	 *
	 * @return void
	 * @throws Exception If the user lacks permissions or save fails.
	 */
	public function save( bool $force_save = false ) {
		$data = get_object_vars( $this );

		// Build meta_input from meta_data.
		if ( ! empty( $this->meta_data ) ) {
			$data['meta_input'] = array();
			foreach ( $this->meta_data as $key_meta => $value_meta ) {
				$data['meta_input'][ $key_meta ] = $value_meta;
			}
		}

		// Remove non-wp_insert_post keys.
		unset( $data['meta_data'], $data['is_got_meta_data'], $data['filter'] );

		if ( empty( $this->ID ) ) {
			// Insert new post.
			if ( ! $force_save && ! $this->check_capabilities_create() ) {
				throw new Exception(
					esc_html__( 'You do not have permission to create this item.', 'wp-events-manager' )
				);
			}

			unset( $data['ID'] );
			$post_id = wp_insert_post( $data, true );
		} else {
			// Update existing post.
			if ( ! $force_save && ! $this->check_capabilities_update() ) {
				throw new Exception(
					esc_html__( 'You do not have permission to edit this item.', 'wp-events-manager' )
				);
			}

			$post_id = wp_update_post( $data, true );
		}

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message() );
		}

		$this->ID = $post_id;

		// Refresh from database to sync all WP-generated fields.
		$post = get_post( $this->ID );
		if ( $post instanceof WP_Post ) {
			foreach ( get_object_vars( $post ) as $property => $value ) {
				if ( property_exists( $this, $property ) ) {
					$this->{$property} = $value;
				}
			}
		}

		$this->clean_caches();
	}

	/**
	 * Delete this post permanently.
	 *
	 * @return void
	 */
	public function delete() {
		wp_delete_post( $this->get_id(), true );
		$this->clean_caches();
	}

	/**
	 * Clean caches.
	 *
	 * Override in child classes for model-specific cache invalidation.
	 *
	 * @return void
	 */
	public function clean_caches() {
		// Child classes may override to clear specific caches.
	}

	/**
	 * Get the post ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return (int) $this->ID;
	}

	/**
	 * Get a single meta value by key.
	 *
	 * Checks in-memory meta_data first, then falls back to database.
	 *
	 * @param string $key           Meta key.
	 * @param mixed  $default_value Default value if not found.
	 * @param bool   $single        Whether to return single value. Default true.
	 *
	 * @return mixed
	 */
	public function get_meta_value_by_key( string $key, $default_value = false, bool $single = true ) {
		// Check in-memory cache.
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return maybe_unserialize( $this->meta_data->{$key} );
		}

		$value = get_post_meta( $this->ID, $key, $single );
		if ( empty( $value ) ) {
			$value = $default_value;
		}

		$value                   = maybe_unserialize( $value );
		$this->meta_data->{$key} = $value;

		return $value;
	}

	/**
	 * Save a single meta value by key.
	 *
	 * @param string $key          Meta key.
	 * @param mixed  $value        Meta value.
	 * @param bool   $force_update Skip capability checks when true.
	 *
	 * @return void
	 * @throws Exception If the user lacks permissions.
	 */
	public function save_meta_value_by_key( string $key, $value, bool $force_update = false ) {
		if ( ! $force_update && ! $this->check_capabilities_update() ) {
			throw new Exception(
				esc_html__( 'You do not have permission to edit this item.', 'wp-events-manager' )
			);
		}

		$this->meta_data->{$key} = $value;
		update_post_meta( $this->ID, $key, $value );
	}

	/**
	 * Get the post thumbnail URL.
	 *
	 * @param string|int[] $size Image size. Default 'post-thumbnail'.
	 *
	 * @return string Image URL or empty string.
	 */
	public function get_image_url( $size = 'post-thumbnail' ): string {
		if ( has_post_thumbnail( $this->ID ) ) {
			$url = get_the_post_thumbnail_url( $this->ID, $size );
			return $url ? $url : '';
		}

		return '';
	}

	/**
	 * Get the permalink.
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		$permalink = get_permalink( $this->ID );

		return $permalink ? $permalink : '';
	}

	/**
	 * Get the post content (filtered).
	 *
	 * @return string
	 */
	public function get_the_content(): string {
		$content = get_the_content( null, false, $this->ID );
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		return $content;
	}

	/**
	 * Get the post excerpt.
	 *
	 * @return string
	 */
	public function get_the_excerpt(): string {
		return get_the_excerpt( $this->ID );
	}

	/**
	 * Get the post title.
	 *
	 * @return string
	 */
	public function get_the_title(): string {
		return get_the_title( $this->ID );
	}

	/**
	 * Get categories of course.
	 *
	 * @return array|WP_Term[]
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function get_terms( string $taxonomy ): array {
		// Todo: set cache.
		$wpPost = new WP_Post( $this );
		$terms  = get_the_terms( $wpPost, $taxonomy );
		if ( ! $terms || $terms instanceof WP_Error ) {
			$terms = array();
		}

		return $terms;
	}

	/**
	 * Get the edit link for this post.
	 *
	 * @return string
	 */
	public function get_edit_link(): string {
		return admin_url( 'post.php?post=' . $this->ID . '&action=edit' );
	}

	/**
	 * Get the human-readable status string.
	 *
	 * @return string
	 */
	public function get_status_i18n(): string {
		switch ( $this->post_status ) {
			case self::STATUS_PUBLISH:
				return __( 'Published', 'wp-events-manager' );
			case self::STATUS_FEATURE:
				return __( 'Scheduled', 'wp-events-manager' );
			case self::STATUS_TRASH:
				return __( 'Trash', 'wp-events-manager' );
			case self::STATUS_DRAFT:
				return __( 'Draft', 'wp-events-manager' );
			case self::STATUS_PRIVATE:
				return __( 'Private', 'wp-events-manager' );
			case self::STATUS_PENDING:
				return __( 'Pending', 'wp-events-manager' );
			case self::STATUS_AUTO_DRAFT:
				return __( 'Auto Draft', 'wp-events-manager' );
			case self::VISIBILITY_PASSWORD:
				return __( 'Protected', 'wp-events-manager' );
			default:
				return ucfirst( $this->post_status );
		}
	}
}
