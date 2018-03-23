<?php

namespace Tests;

use RS\Form\FormServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
          FormServiceProvider::class,
        ];
    }
}