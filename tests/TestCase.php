<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('127.0.0.1', config('database.connections.mysql.host'));
        $this->assertSame('recipe_test', DB::selectOne('SELECT DATABASE() AS db')->db,
            'Refusing to test outside the local recipe_test database.');
    }
}
