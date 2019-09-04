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
		  __DIR__.'/resources/views' => resource_path('views/vendor/form'),
		],'form');

        $this->addBladeDirectives();


    }

    public function register(){
		$this->app->resolving(Formlet::class, function (Formlet $formlet, $app) {
			$formlet->setSessionStore($app['session.store']);
			$formlet->setUrlGenerator($app['url']);
			$formlet->setRequest($app['request']);
            $formlet->initialize();
		});
	}

    protected function addBladeDirectives(): void
    {
        Blade::component('form::components.form', 'form');

        Blade::directive('field', function ($expression) {

            $vars = explode(',', $expression);

            if($vars[0] == ""){
                $vars = [];
            }

            if (count($vars) == 2) {
                list($formlet, $field) = $vars;

                $accessor = "[$formlet]->field($field)";

            } elseif((count($vars) == 1)) {
                list($field) = $vars;
                $accessor = "['formlet']->field({$field})";
            } else {
                $accessor = "['field']";
            }

            return "<?php echo \Illuminate\Support\Arr::except(get_defined_vars(), array('__data', '__path')){$accessor}->render(); ?>";
        });

        Blade::directive('formlet', function ($expression) {

            if ($expression == "") {
                $accessor = "['formlet']";
            } else {
                list($name) = explode(',', $expression);
                $accessor = "[$name]";
            }

            return "<?php foreach(\Illuminate\Support\Arr::except(get_defined_vars(), array('__data', '__path')){$accessor}->fields() as \$field){
                echo \$field->render(); 
            } ?>";
        });


    }

}
