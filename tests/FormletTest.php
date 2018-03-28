<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RS\Form\Fields\Checkbox;
use RS\Form\Fields\Hidden;
use RS\Form\Fields\Input;
use RS\Form\Fields\Radio;
use RS\Form\Fields\Select;
use RS\Form\Formlet;


class FormletTest extends TestCase
{
    use InteractsWithSession;

    /** @var Request */
    protected $request;

    protected function setUp()
    {
        parent::setUp();
        $this->request = $this->app['request'];
    }

    /** @test */
    public function the_form_has_default_attributes()
    {
        $form = $this->formlet();
        $this->assertEquals('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertEquals('UTF-8', $form->getAttribute('accept-charset'));
        $this->assertEquals('POST', $form->getAttribute('method'));
    }

    /** @test */
    public function it_can_set_a_form_method()
    {
        $form = $this->formlet();
        $this->assertEquals('POST', $form->getAttribute('method'));
        $form->method('put');
        $this->assertEquals('PUT', $form->getAttribute('method'));
        $form->method('DELETE');
        $this->assertEquals('DELETE', $form->getAttribute('method'));
    }

    /** @test */
    public function it_can_set_a_route()
    {
        Route::get('/tests', function () {
        })->name('tests.index');
        Route::put('/tests/{id}', function () {
        })->name('tests.update');
        Route::delete('/tests', function () {
        })->name('tests.destroy');

        $form = $this->formlet();

        $this->assertEquals('http://localhost', $form->getAttribute('action'));

        $form->route('tests.destroy');
        $this->assertEquals('http://localhost/tests', $form->getAttribute('action'));
        $this->assertEquals('DELETE', $form->getAttribute('method'));

        $form->route('tests.update', ['id' => 4]);
        $this->assertEquals('http://localhost/tests/4', $form->getAttribute('action'));
        $this->assertEquals('PUT', $form->getAttribute('method'));

        $this->expectException(\InvalidArgumentException::class);
        $form->route('fake');
    }

    /** @test */
    public function a_csrf_field_is_added_to_the_form()
    {
        $form = $this->formlet();
        $token = 'abc';
        $this->session(['_token'=>$token]);
        $data = $form->build();

        $field = $data->get('_hidden')->get('token');
        $this->assertInstanceOf(Hidden::class, $field);
        $this->assertEquals('_token', $field->getName());
        $this->assertEquals($token, $field->getValue());
    }

    public function getFormMethods()
    {
        return [
          ['GET', false],
          ['PUT', true],
          ['POST', false],
          ['DELETE', true],
          ['PATCH', true],
        ];
    }

    /**
     * @test
     * @dataProvider getFormMethods
     */
    public function a_method_field_is_added_to_the_form_if_required($method, $required)
    {
        $form = $this->formlet();
        $form->method($method);
        $data = $form->build();

        $field = $data->get('_hidden')->get('method');

        if ($required) {
            $this->assertInstanceOf(Hidden::class, $field);
            $this->assertEquals('_method', $field->getName());
            $this->assertEquals($method, $field->getValue());
        } else {
            $this->assertNull($field);
        }
    }

    /** @test */
    public function can_add_fields_to_a_form()
    {

        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'foo'));
            $form->add(new Input('email', 'bim'));
            $form->add(new Select('bar'));
        });

        $form->build();

        $this->assertCount(3, $form->fields());
        $this->assertInstanceOf(Input::class, $form->fields('foo')->first());
        $this->assertInstanceOf(Select::class, $form->fields('bar')->first());
        $this->assertCount(2, $form->fields(['foo', 'bar']));
        $this->assertInstanceOf(Input::class, $form->fields(['foo', 'bar'])->get('foo'));
        $this->assertInstanceOf(Select::class, $form->fields(['foo', 'bar'])->get('bar'));
    }

    /** @test */
    public function fields_can_be_filled_by_the_request()
    {

        $this->request->merge([
          'name'=> 'foo',
          'person'=>['name'=>'John'],
          'agree' => 1,
          'radio'=> 'foo'
        ]);

        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'name'));
            $form->add(new Input('text', 'person[name]'));
            $form->add(new Checkbox('agree'));
            $form->add(new Checkbox('novalue'))->multiple([



            ]);
            $form->add(new Radio('radio',[
              'foo'=> 'bar',
              'bim'=> 'baz'
            ]));

            (new Select('foo'))->multiple();
        });

        $form->build();
        $fields = $form->fields();

        $this->assertEquals('foo', $fields->get('name')->getValue());
        $this->assertEquals('John', $fields->get('person[name]')->getValue());
        $this->assertTrue($fields->get('agree')->getValue());
        $this->assertFalse($fields->get('novalue')->getValue());
        $this->assertEquals('foo',$fields->get('radio')->getValue());

    }

    private function formlet(\Closure $closure = null): TestFormlet
    {
        return $this->app->makeWith(TestFormlet::class, ['closure' => $closure]);
    }

}

class TestFormlet extends Formlet
{

    protected $closure;

    public function __construct(\Closure $closure =null)
    {
        $this->closure = $closure;
    }

    public function prepare(): void
    {
        $closure = $this->closure;
        if (!is_null($closure)) {
            $closure($this);
        }
    }

}