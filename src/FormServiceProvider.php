<?php

namespace RS\Form;

use Illuminate\Support\ServiceProvider;
use RS\Form\Console\FormletMakeCommand;

class FormServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

		if ($this->app->runningInConsole()) {
			$this->commands([
			  FormletMakeCommand::class
			]);
		}

		$this->loadViewsFrom(__DIR__.'/resources/views', 'form');

		$this->publishes([
		  __DIR__.'/resources/views' => resource_path('views/form'),
		  //__DIR__.'/View' => app_path('View')
		],'form');

    }

    public function register(){
		$this->app->resolving(Formlet::class, function (Formlet $formlet, $app) {
			$formlet->setSessionStore($app['session.store']);
			$formlet->setUrlGenerator($app['url']);
			$formlet->setRequest($app['request']);
            $formlet->initialize();
		});
	}

}
