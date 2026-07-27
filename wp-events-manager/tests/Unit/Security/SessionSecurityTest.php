<?php
/**
 * Session security tests.
 *
 * @package WPEMS\Tests\Unit\Security
 */

namespace WPEMS\Tests\Unit\Security;

use Brain\Monkey\Functions;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test session deserialization hardening.
 */
class SessionSecurityTest extends TestCase {

	/**
	 * Prepare session state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'COOKIEHASH' ) ) {
			define( 'COOKIEHASH', 'test' );
		}

		$_SESSION = array();

		require_once WPEMS_INC . 'class-wpems-session.php';
		Functions\when( 'add_action' )->justReturn( true );
	}

	/**
	 * It does not rehydrate objects from serialized session values.
	 *
	 * @return void
	 */
	public function test_session_removes_serialized_objects(): void {
		$_SESSION['event_auth_session_test'] = serialize(
			array(
				'notices' => 'O:8:"stdClass":0:{}',
			)
		);

		$session = new \WPEMS_Session();

		$this->assertNull( $session->get( 'notices' ) );
	}

	/**
	 * It preserves normal serialized arrays.
	 *
	 * @return void
	 */
	public function test_session_preserves_serialized_arrays(): void {
		$_SESSION['event_auth_session_test'] = serialize(
			array(
				'notices' => serialize(
					array(
						'success' => array( 'ok' ),
					)
				),
			)
		);

		$session = new \WPEMS_Session();

		$this->assertSame(
			array(
				'success' => array( 'ok' ),
			),
			$session->get( 'notices' )
		);
	}
}
