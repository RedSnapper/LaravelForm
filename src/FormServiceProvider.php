<?php

namespace RS\Form;

use Illuminate\Support\Facades\Blade;
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
		],'form');

        Blade::component('form::components.form', 'form');

        Blade::directive('field',function($expression){

            $vars = explode(',',$expression);

            if(count($vars) == 2){
                list($form,$field) = $vars;
                $accessor = "['formlets'][$form][0]['fields'][{$field}]";
            }else{
                list($field) = $vars;
                $accessor = "['formlet']['fields'][{$field}]";
            }

            return "<?php echo \array_except(get_defined_vars(), array('__data', '__path')){$accessor}->render(); ?>";
        });

        Blade::directive('formlet',function($expression){

            if($expression == ""){
                $accessor = "['formlet']";
            }else{
                list($name) = explode(',',$expression);
                $accessor = "['formlets'][{$name}][0]";
            }

            return "<?php foreach(array_except(get_defined_vars(), array('__data', '__path')){$accessor}['fields'] as \$field){
                echo \$field->render(); 
            } ?>";
        });


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
