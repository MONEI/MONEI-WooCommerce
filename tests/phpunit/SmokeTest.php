<?php
/**
 * Proves the PHPUnit harness runs and that its bootstrap contract holds.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Helpers\CardBrandHelper;
use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase {

	public function test_harness_runs() {
		$this->assertTrue( true );
	}

	public function test_bootstrap_defines_abspath() {
		// Guard for the whole suite: without ABSPATH, loading any plugin class
		// that opens with the ABSPATH check exits the process instead of failing.
		$this->assertTrue( defined( 'ABSPATH' ) );
	}

	public function test_plugin_namespace_autoloads() {
		$this->assertTrue( class_exists( CardBrandHelper::class ) );
	}
}
