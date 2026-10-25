<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestEnvironment;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        // Do not call parent::createApplication(): it boots providers before returning.
        TestEnvironment::preflight(dirname(__DIR__));
        $app = require dirname(__DIR__).'/bootstrap/app.php';
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        TestEnvironment::protect($app);
        $app->make(Kernel::class)->bootstrap();

        // Livewire temporarily overrides these static views. Aborted hostile requests
        // can skip its destroy hook; never carry that override into another test app.
        \Illuminate\Pagination\Paginator::defaultView('pagination::tailwind');
        \Illuminate\Pagination\Paginator::defaultSimpleView('pagination::simple-tailwind');

        return $app;
    }
}
