<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        $this->request->merge($this->validPost());
        $form = $this->form();

        $form->validate(false);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->errors());
    }

    /** @test */
    public function it_fails_validation()
    {
        $form = $this->form();

        $form->validate(false);

        $this->assertFalse($form->isAllValid());

        $errors = $form->allErrors();

        $this->assertCount(3, $errors);
        $this->assertEquals(["The name field is required."], $errors->get('name'));
        $this->assertEquals(["The country field is required."], $errors->get('child.0.country'));

        $errors = $form->errors();
        $this->assertFalse($form->isValid());
        $this->assertCount(2, $errors);
        $this->assertEquals(["The name field is required."], $errors->get('name'));

        $childForm = $form->formlet('child');
        $errors = $childForm->errors();
        $this->assertFalse($childForm->isValid());
        $this->assertCount(1, $errors);
        $this->assertEquals(["The country field is required."], $errors->get('country'));

    }

    /** @test */
    public function it_can_automatically_redirect_after_failing_validation()
    {

        Route::post('/test', function () {
            $form = $this->form();
            $form->validate();
        });

        $this->from('/test')
          ->post('/test', [])
          ->assertRedirect('/test')
          ->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function it_does_not_automatically_redirect_after_passing_validation()
    {

        Route::post('/test', function () {
            $form = $this->form();
            $form->validate();
        });

        $this->post('/test', $this->validPost())
          ->assertStatus(200)
          ->assertSessionMissing('errors');
    }

    /** @test */
    public function can_add_custom_messages_to_validation()
    {
        $form = $this->form();

        $form->validate(false);
        $errors = $form->allErrors();

        $this->assertEquals(["An Email Address is needed."], $errors->get('email'));
    }

    /** @test */
    public function can_retrieve_errors_from_session()
    {
        $this->session([
          'errors' => [
            'name'            => ['Session error'],
            'child.0.country' => ['Country error']
          ]
        ]);

        $form = $this->form();
        $form->build();

        $errors = $form->allErrors();
        $fields = $form->fields();
        $childFormlet = $form->formlet('child');


        $this->assertEquals(["Session error"], $errors->get('name'));
        $this->assertEquals(["Session error"], $fields->get('name')->getErrors()->toArray());

        $this->assertEquals(["Country error"], $errors->get('child.0.country'));
        $this->assertEquals(["Country error"], $childFormlet->field('country')->getErrors()->toArray());
    }

    /** @test */
    public function can_set_a_redirect_route_on_validation_failure()
    {

        Route::post('/test', function () {
            $form = $this->form(function (Formlet $form) {
                $form->redirectRoute = "redirect";
            });
            $form->validate();
        });

        Route::get('/redirect', function () {
        })->name('redirect');

        $this->from('/test')
          ->post('/test', ['name' => ''])
          ->assertRedirect('/redirect')
          ->assertSessionHasErrors(['name']);
    }

    private function form(\Closure $closure = null): Formlet
    {
        return $this->app->makeWith(ValidationFormlet::class, ['closure' => $closure]);
    }

    protected function validPost()
    {
        return [
          'name'  => 'John',
          'email' => 'john@example.com',
          'child' => [
            ['country' => 'England']
          ]
        ];
    }

}

class ValidationFormlet extends Formlet
{

    public $redirectRoute;

    protected $closure;

    public function __construct(\Closure $closure = null)
    {
        if (!is_null($closure)) {
            $closure($this);
        }
    }

    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
        $this->addFormlet('child', ChildValidationFormlet::class);
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
          'email' => 'Email Address'
        ];
    }

}

class ChildValidationFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'country'));
    }

    public function rules(): array
    {
        return [
          'country' => 'required'
        ];
    }
}