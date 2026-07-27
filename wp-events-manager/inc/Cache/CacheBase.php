<?php

namespace WPEMS\Cache;

defined( 'ABSPATH' ) || exit();

/**
 * Class CacheBase
 *
 * Base class for caching functionality.
 *
 * @since 2.2.5
 * @version 1.0.0
 */
class CacheBase {
	/**
	 * @var string Key group parent
	 */
	protected $key_group_parent = 'wpems';

	/**
	 * @var string Key group child(external)
	 */
	protected $key_group_child = '';

	/**
	 * @var string Add key group parent with key group child
	 */
	protected $key_group = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->key_group = $this->key_group_parent . '/' . $this->key_group_child;
	}

	/**
	 * Set cache
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @param int    $expire
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function set_cache( string $key, $data, int $expire = 0 ) {
		wp_cache_set( $key, $data, $this->key_group, $expire );
	}

	/**
	 * Get cache
	 *
	 * @param string $key
	 * @return false|mixed
	 */
	public function get_cache( string $key ) {
		return wp_cache_get( $key, $this->key_group );
	}

	/**
	 * Clear cache by key
	 *
	 * @param string $key
	 */
	public function clear( string $key ) {
		if ( empty( $key ) ) {
			return;
		}

		wp_cache_delete( $key, $this->key_group );
	}
}
