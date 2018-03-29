<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RS\Form\Fields\Input;
use RS\Form\Formlet;

class FormletValidationTest extends TestCase
{
    use InteractsWithSession;

    /** @var Formlet */
    protected $form;

    /** @var Request */
    protected $request;

    protected function setUp()
    {
        parent::setUp();
        $this->request = $this->app['request'];

    }

    /** @test */
    public function it_passes_validation()
    {
        $this->request->merge(['name' => 'John','email'=>'john@example.com']);
        $form = $this->form();

        $form->validate(false);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->getErrors());
    }

    /** @test */
    public function it_fails_validation()
    {
        $form = $this->form();

        $form->validate(false);

        $this->assertFalse($form->isValid());

        $errors = $form->getErrors();

        $this->assertCount(2, $errors);
        $this->assertEquals(["The name field is required."], $errors->get('name'));
    }

    /** @test */
    public function it_can_automatically_redirect_after_failing_validation()
    {
        $form = $this->form();

        $this->request->merge(['name' => '']);

        Route::post('/test', function () use ($form) {
            $form->validate();
        });

        $this->post('/test', ['name' => ''])
          ->assertRedirect('/')
          ->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function it_does_not_automatically_redirect_after_passing_validation()
    {
        $form = $this->form();

        $this->request->merge(['name' => 'John','email'=>'john@example.com']);

        Route::post('/test', function () use ($form) {
            $form->validate();
        });

        $this->post('/test', ['name' => ''])
          ->assertStatus(200)
          ->assertSessionMissing('errors');
    }

    /** @test */
    public function can_add_custom_messages_to_validation()
    {
        $form = $this->form();

        $form->validate(false);
        $errors = $form->getErrors();

        $this->assertEquals(["An Email Address is needed."], $errors->get('email'));
    }

    /** @test */
    public function can_retrieve_errors_from_session()
    {
        $this->session(['errors'=>[
          'name'=> ['Session error']
        ]]);

        $form = $this->form();
        $form->build();

        $errors = $form->getErrors();
        $fields = $form->fields();

        $this->assertEquals(["Session error"], $errors->get('name'));
        //$this->assertEquals(["Session error"],$fields->get('name')->getErrors()->toArray());


    }

    protected function form():Formlet{
        return app(ValidationFormlet::class);
    }

}

class ValidationFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
    }

    public function rules(): array
    {
        return [
          'name'  => 'required',
          'email' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
          'email.required' => 'An :attribute is needed.',
        ];
    }

    public function attributes(): array
    {
        return [
          'email'=> 'Email Address'
        ];
    }



}