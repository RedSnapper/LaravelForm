<?php

namespace RS\Form;

use Illuminate\Support\ServiceProvider;

class FormServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
		$this->app->resolving(Formlet::class, function (Formlet $formlet, $app) {
			$formlet->setSessionStore($app['session.store']);
			$formlet->setUrlGenerator($app['url']);
			$formlet->setRequest($app['request']);
		});

		$this->loadViewsFrom(__DIR__.'/resources/views', 'form');

		$this->publishes([
		  __DIR__.'/resources/views' => resource_path('views/form'),
		  __DIR__.'/View' => app_path('View')
		],'form');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
