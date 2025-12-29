<?php

namespace Denizgolbas\EloquentHasduplicateAttirbutes\Tests;

use Denizgolbas\EloquentHasduplicateAttirbutes\EloquentHasduplicateAttirbutesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            EloquentHasduplicateAttirbutesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}

