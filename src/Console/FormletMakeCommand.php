<?php

namespace RS\Form\Console;

use Illuminate\Console\GeneratorCommand;

class FormletMakeCommand extends GeneratorCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:formlet';
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new formlet class';
	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Formlet';
	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/form.stub';
	}
	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace.'\Http\Formlets';
	}
}