<?php
namespace WPEMS\Models;

use WPEMS\Cache\CacheBase;
use WPEMS\Databases\UserDB;
use WPEMS\Filters\UserFilter;
use Exception;
use stdClass;
use Throwable;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Class UserModel
 *
 * @version 1.0.0
 * @since 1.0.0
 */
class UserModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	public $ID = 0;
	/**
	 * @var string author id, foreign key
	 */
	public $user_login = 0;
	/**
	 * @var string user nicename, instead slug of username
	 */
	public $user_nicename;
	/**
	 * @var string user email
	 */
	public $user_email = null;
	/**
	 * @var string user url
	 */
	public $user_url = null;
	/**
	 * Display name of user.
	 *
	 * @var string
	 */
	public $display_name = '';
	/**
	 * @var stdClass all meta data
	 */
	public $meta_data = null;

	// Roles
	const ROLE_ADMINISTRATOR = 'administrator';

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
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
	 * Map array, object data to UserItemModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return UserModel
	 */
	public function map_to_object( $data ): UserModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get user by ID
	 *
	 * @param int  $user_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $user_id, bool $check_cache = true ) {
		$filter_user     = new UserFilter();
		$filter_user->ID = $user_id;
		$key_cache       = "userModel/find/id/{$user_id}";
		$cache           = new CacheBase();

		// Check cache
		if ( $check_cache ) {
			$user_model = $cache->get_cache( $key_cache );
			if ( $user_model instanceof UserModel ) {
				return $user_model;
			}
		}

		// Query database no cache.
		$user_model = self::get_user_model_from_db( $filter_user );

		// Set cache
		if ( $user_model instanceof UserModel ) {
			$cache->set_cache( $key_cache, $user_model );
		}

		return $user_model;
	}

	/**
	 * Get course from database.
	 * If not exists, return false.
	 * If exists, return CoursePostModel.
	 *
	 * @param UserFilter $filter
	 *
	 * @return UserModel|false|static
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public static function get_user_model_from_db( UserFilter $filter ) {
		$user_db    = UserDB::getInstance();
		$user_model = false;

		try {
			$filter->only_fields = [ 'ID', 'user_nicename', 'user_login', 'user_email', 'display_name' ];
			$user_db->get_query_single_row( $filter );
			$query_single_row = $user_db->get_users( $filter );
			$user_rs          = $user_db->wpdb->get_row( $query_single_row );
			if ( $user_rs instanceof stdClass ) {
				$user_model = new UserModel( $user_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $user_model;
	}

	/**
	 * Get meta value by key.
	 *
	 * @param string $key
	 * @param mixed  $default_value
	 * @param bool   $single
	 *
	 * @return false|mixed
	 */
	public function get_meta_value_by_key( string $key, $default_value = false, bool $single = true ) {
		if ( $this->meta_data instanceof stdClass && isset( $this->meta_data->{$key} ) ) {
			return $this->meta_data->{$key};
		}

		$value = get_user_meta( $this->ID, $key, $single );
		if ( empty( $value ) ) {
			$value = $default_value;
		}

		$this->meta_data->{$key} = $value;

		return $value;
	}

	/**
	 * Set meta value by key.
	 *
	 * @param string $key
	 * @param        $value
	 *
	 * @return void
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function set_meta_value_by_key( string $key, $value ) {
		$this->meta_data->{$key} = $value;
		update_user_meta( $this->ID, $key, $value );
	}

	/**
	 * Get display name
	 *
	 * Hook from function get_the_author_meta of WP
	 *
	 * @return string
	 * @uses get_the_author_meta
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function get_display_name(): string {
		return apply_filters(
			'get_the_author_display_name',
			$this->display_name,
			$this->get_id(),
			$this->get_id()
		);
	}

	/**
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return UserModel
	 * @throws Exception
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function save(): UserModel {
		// Clear caches.
		$this->clean_caches();

		return $this;
	}

	/**
	 * Clean caches.
	 *
	 * @return void
	 */
	public function clean_caches() {
		// Clear cache.
		$key_cache = "userModel/find/id/{$this->get_id()}";
		$cache     = new CacheBase();
		$cache->clear( $key_cache );
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return (int) $this->ID;
	}

	/**
	 * Get description of user.
	 *
	 * @return string
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_description(): string {
		return get_the_author_meta( 'description', $this->get_id() );
	}

	/**
	 * Get email of user.
	 *
	 * @return string
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_email(): string {
		return $this->user_email ?? '';
	}

	/**
	 * Get username of user.
	 *
	 * @return string
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_username(): string {
		return $this->user_login ?? '';
	}

	/**
	 * Get roles of user.
	 *
	 * @return string[]
	 */
	public function get_roles(): array {
		$user = get_userdata( $this->ID );

		return ( $user instanceof WP_User && is_array( $user->roles ) ) ? $user->roles : array();
	}
}
