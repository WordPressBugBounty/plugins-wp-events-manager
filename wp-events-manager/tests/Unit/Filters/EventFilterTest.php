<?php
/**
 * Event filter tests.
 *
 * @package WPEMS\Tests\Unit\Filters
 */

namespace WPEMS\Tests\Unit\Filters;

use WPEMS\Filters\EventFilter;
use WPEMS\Filters\PostFilter;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test event query filter data object.
 */
class EventFilterTest extends TestCase {

	/**
	 * It starts with event query defaults.
	 *
	 * @return void
	 */
	public function test_constructor_defaults(): void {
		$filter = new EventFilter();

		$this->assertInstanceOf( PostFilter::class, $filter );
		$this->assertSame( 0, $filter->ID );
		$this->assertSame( 'tp_event', $filter->post_type );
		$this->assertSame( 10, $filter->limit );
		$this->assertSame( array(), $filter->post_status );
		$this->assertSame( 'post_date', $filter->order_by );
		$this->assertSame( 'DESC', $filter->order );
	}

	/**
	 * It allows direct property assignment.
	 *
	 * @return void
	 */
	public function test_property_assignment(): void {
		$filter            = new EventFilter();
		$filter->ID        = 55;
		$filter->status    = 'publish';
		$filter->date_from = '2026-01-01';
		$filter->date_to   = '2026-02-01';

		$this->assertSame( 55, $filter->ID );
		$this->assertSame( 'publish', $filter->status );
		$this->assertSame( '2026-01-01', $filter->date_from );
		$this->assertSame( '2026-02-01', $filter->date_to );
	}

	/**
	 * It allows pagination and sort configuration.
	 *
	 * @return void
	 */
	public function test_sort_and_pagination_properties_are_mutable(): void {
		$filter           = new EventFilter();
		$filter->limit    = 25;
		$filter->offset   = 50;
		$filter->order_by = 'post_title';
		$filter->order    = 'ASC';

		$this->assertSame( 25, $filter->limit );
		$this->assertSame( 50, $filter->offset );
		$this->assertSame( 'post_title', $filter->order_by );
		$this->assertSame( 'ASC', $filter->order );
	}
}
