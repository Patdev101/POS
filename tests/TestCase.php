<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must never reach a real Inventory server; anything not
        // explicitly faked fails loudly instead of changing real stock.
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }

    use RefreshDatabase;
}
