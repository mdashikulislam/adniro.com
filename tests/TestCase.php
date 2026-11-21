<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
	/**
	 * adniro: refuse to run against any database but a test one. RefreshDatabase drops every
	 * table, so a missing DB_DATABASE (e.g. a phpunit config without it) would wipe the real
	 * database named in .env.
	 */
	protected function setUpTraits()
	{
		$connection = config('database.default');
		$database = (string)config('database.connections.' . $connection . '.database');
		if ($connection !== 'sqlite' && !str_ends_with($database, '_test')) {
			throw new \RuntimeException("Tests must use a database whose name ends with \"_test\"; got \"{$database}\". Run them with phpunit.xml.");
		}

		return parent::setUpTraits();
	}

	/**
	 * Setup the test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		
		// Install fresh data
		// $this->artisan('app:install-fresh-data', ['--confirm' => true]);
	}
	
	/**
	 * Clean up the testing environment before the next test.
	 *
	 * @return void
	 *
	 * @throws \Mockery\Exception\InvalidCountException
	 */
	protected function tearDown(): void
	{
		parent::tearDown();
	}
}
