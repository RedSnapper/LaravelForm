<?php

namespace Tests;

use RS\Form\Fields\AbstractField;
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

    protected function getEnvironmentSetUp($app)
    {
        $app->make('Illuminate\Contracts\Http\Kernel')->pushMiddleware('Illuminate\Session\Middleware\StartSession');
        $app['config']->set('database.default', 'testing');
    }

    protected function renderField(AbstractField $field)
    {
        return $field->render()->render(function ($view, $contents) {
            $string = preg_replace('/[\n\t\r]+/', '', $contents);
            return preg_replace('/[ ]{2,}/', ' ', $string);
        });
    }

}