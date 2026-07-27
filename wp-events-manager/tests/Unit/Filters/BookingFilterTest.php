<?php
/**
 * Booking filter tests.
 *
 * @package WPEMS\Tests\Unit\Filters
 */

namespace WPEMS\Tests\Unit\Filters;

use WPEMS\Filters\BookingFilter;
use WPEMS\Filters\PostFilter;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test booking query filter defaults.
 */
class BookingFilterTest extends TestCase {

	/**
	 * It starts with booking query defaults.
	 *
	 * @return void
	 */
	public function test_constructor_defaults(): void {
		$filter = new BookingFilter();

		$this->assertInstanceOf( PostFilter::class, $filter );
		$this->assertSame( 0, $filter->ID );
		$this->assertSame( 'event_auth_book', $filter->post_type );
		$this->assertSame( 10, $filter->limit );
		$this->assertSame( array(), $filter->post_status );
	}
}
