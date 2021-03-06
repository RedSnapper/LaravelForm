<?php

namespace RS\Form\Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use RS\Form\Fields\Input;
use RS\Form\Formlet;
use Symfony\Component\HttpFoundation\FileBag;

class FormletValidationTest extends TestCase
{
    use InteractsWithSession;

    /** @var Formlet */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var UploadedFile */
    protected $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->app['request'];
        $this->file = UploadedFile::fake()->create('test');

        $this->request->files = new FileBag([
          'file' => $this->file,
          'child' => [['file' => $this->file]]
        ]);
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
        $this->request->files = new FileBag();

        $form = $this->form();

        $form->validate(false);

        $this->assertFalse($form->isAllValid());

        $errors = $form->allErrors();

        $this->assertCount(5, $errors);
        $this->assertEquals(["The name field is required."], $errors->get('name'));
        $this->assertEquals(["The country field is required."], $errors->get('child.0.country'));
        $this->assertEquals(["The file field is required."], $errors->get('child.0.file'));

        $errors = $form->errors();
        $this->assertFalse($form->isValid());
        $this->assertCount(3, $errors);
        $this->assertEquals(["The name field is required."], $errors->get('name'));

        $childForm = $form->formlet('child');
        $errors = $childForm->errors();
        $this->assertFalse($childForm->isValid());
        $this->assertCount(2, $errors);
        $this->assertEquals(["The country field is required."], $errors->get('country'));
        $this->assertEquals(["The file field is required."], $errors->get('file'));
    }

    /** @test */
    public function throws_a_validation_exception_when_validation_fails()
    {
        $this->withoutExceptionHandling();
        $this->expectException(ValidationException::class);
        $this->form()->validate();
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
        $this->post('/test', $this->validPost(true))
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
        $viewBag = new ViewErrorBag();
        $errorBag = new MessageBag([
          'name' => ['Session error'],
          'child.0.country' => ['Country error']
        ]);

        $viewBag->put('default', $errorBag);

        $this->session([
          'errors' => $viewBag
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

        $this->assertEquals(["Session error"], $form->error('name'));
        $this->assertEquals(["Country error"], $childFormlet->error('country'));
    }

    /** @test */
    public function can_populate_formlets_with_session_errors()
    {

        Route::post('/user', function () {
            $form = $this->form();
            $form->validate();
        });

        Route::get('/user', function () {
            $form = $this->form();
            return $form->build();
        });

        $formlet = $this->from('/user')
          ->followingRedirects()
          ->post('/user')
          ->getOriginalContent()->get('formlet');

        $this->assertEquals(["The name field is required."], $formlet->error('name'));
        $this->assertEquals(["An Email Address is needed."], $formlet->error('email'));
        $this->assertEquals(["The country field is required."], $formlet->formlet('child')->error('country'));
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

    /** @test */
    public function form_with_prefix_passes_validation()
    {
        $this->request->merge($this->validPrefixPost());
        $form = $this->prefixForm();

        $form->validate(false);

        $this->assertTrue($form->isValid());
        $this->assertCount(0, $form->errors());
    }

    /** @test */
    public function prefix_form_fails_validation()
    {
        $form = $this->prefixForm();

        $form->validate(false);

        $this->assertFalse($form->isAllValid());

        $errors = $form->allErrors();

        $this->assertCount(1, $errors);
        $this->assertEquals(["Countries are needed."], $errors->get('country'));
    }

    /** @test */
    public function it_can_automatically_redirect_after_failing_validation_prefix_form()
    {

        Route::post('/test', function () {
            $form = $this->prefixForm();
            $form->validate();
        });

        $this->from('/test')
          ->post('/test', [])
          ->assertRedirect('/test')
          ->assertSessionHasErrors(['country'], null, 'prefix');
    }

    /** @test */
    public function can_retrieve_errors_from_session_using_a_different_error_bag()
    {
        $viewBag = new ViewErrorBag();
        $errorBag = new MessageBag([
          'country' => ['Session error'],
        ]);

        $viewBag->put('prefix', $errorBag);

        $this->session([
          'errors' => $viewBag
        ]);

        $form = $this->prefixForm();
        $form->build();

        $errors = $form->allErrors();
        $fields = $form->fields();

        $this->assertEquals(["Session error"], $errors->get('country'));
        $this->assertEquals(["Session error"], $fields->get('country')->getErrors()->toArray());
    }

    /** @test */
    public function can_configure_the_validator_instance_for_this_formlet()
    {
        // We can use the withValidator method to configure our validator instance

        $this->request->merge(['prefix:country' => 3]);
        $form = $this->prefixForm();

        $form->validate(false);

        $this->assertFalse($form->isValid());
        $errors = $form->allErrors();

        $this->assertCount(1, $errors);
        $this->assertEquals(["The Countries must be a string."], $errors->get('country'));
    }

    /** @test */
    public function test_prepareForValidation_runs_before_validation()
    {
        $form = $this->createFormlet(HooksFormlet::class);
        $form->validate();
        $this->assertEquals('John', $form->postData('name'));
        $this->assertEquals('John', $form->field('name')->getValue());
    }

    /** @test */
    public function test_prepareForValidation_does_not_run_when_building_the_form()
    {
        $form = $this->createFormlet(HooksFormlet::class);
        $form->build();
        $this->assertNull($form->field('name')->getValue());
    }

    private function form(\Closure $closure = null): Formlet
    {
        return $this->createFormlet(ValidationFormlet::class, $closure);
    }

    private function prefixForm(\Closure $closure = null): Formlet
    {
        return $this->createFormlet(PrefixFormlet::class, $closure);
    }

    private function createFormlet(string $class, \Closure $closure = null): Formlet
    {
        return $this->app->makeWith($class, ['closure' => $closure]);
    }

    protected function validPost($includeFile = false)
    {

        $data = [
          'name' => 'John',
          'email' => 'john@example.com',
          'child' => [
            ['country' => 'England']
          ]
        ];

        if ($includeFile) {
            $data['file'] = $this->file;
            $data['child'][0]['file'] = $this->file;
        }

        return $data;
    }

    protected function validPrefixPost()
    {
        return ['prefix:country' => 'England'];
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
        $this->add(new Input('file', 'file'));
        $this->addFormlet('child', ChildValidationFormlet::class);
    }

    public function rules(): array
    {
        return [
          'name' => 'required',
          'email' => 'required',
          'file' => 'required'
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
        $this->add(new Input('file', 'file'));
    }

    public function rules(): array
    {
        return [
          'country' => 'required',
          'file' => 'required'
        ];
    }
}

class PrefixFormlet extends Formlet
{

    /**
     * PrefixFormlet constructor.
     */
    public function __construct()
    {
        $this->prefix = "prefix";
    }

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

    public function messages(): array
    {
        return [
          'country.required' => ':attribute are needed.',
          'country.string' => 'The :attribute must be a string.',
        ];
    }

    public function attributes(): array
    {
        return [
          'country' => 'Countries'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->addRules(['country' => 'string']);
    }

}

class HooksFormlet extends Formlet
{
    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
    }

    public function rules(): array
    {
        return [
          'name' => 'required'
        ];
    }

    protected function prepareForValidation()
    {
        $this->mergeInput(['name' => 'John']);
    }
}