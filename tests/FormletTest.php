<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use RS\Form\Fields\Checkbox;
use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Hidden;
use RS\Form\Fields\Input;
use RS\Form\Fields\Radio;
use RS\Form\Fields\Select;
use RS\Form\Formlet;
use stdClass;

class FormletTest extends TestCase
{
    use InteractsWithSession;

    /** @var Request */
    protected $request;

    protected function setUp()
    {
        parent::setUp();
        $this->request = $this->app['request'];

        $this->request->merge([
          'main' => [
            [
              'name'   => 'foo',
              'person' => ['name' => 'John'],
              'agree'  => 'Yes',
              'radio'  => 'foo',
              'cb'     => [1, 2]
            ]
          ]
        ]);
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
        $this->assertEquals('POST', $form->getMethod());
        $form->method('put');
        $this->assertEquals('PUT', $form->getMethod());
        $this->assertEquals('POST', $form->getAttribute('method'));
        $form->method('DELETE');
        $this->assertEquals('DELETE', $form->getMethod());
        $this->assertEquals('POST', $form->getAttribute('method'));
        $form->method('get');
        $this->assertEquals('GET', $form->getMethod());
        $this->assertEquals('GET', $form->getAttribute('method'));
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
        $this->assertEquals('DELETE', $form->getMethod());

        $form->route('tests.update', ['id' => 4]);
        $this->assertEquals('http://localhost/tests/4', $form->getAttribute('action'));
        $this->assertEquals('PUT', $form->getMethod());

        $this->expectException(\InvalidArgumentException::class);
        $form->route('fake');
    }

    /** @test */
    public function a_csrf_field_is_added_to_the_form()
    {
        $form = $this->formlet();
        $token = 'abc';
        $this->session(['_token' => $token]);
        $data = $form->build();

        $field = $data->get('form')->get('hidden')->get('token');
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

        $field = $data->get('form')->get('hidden')->get('method');

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
    public function can_add_a_formlet_to_a_form()
    {
        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'foo'));
            $form->addFormlet('child', ChildFormlet::class);
            $form->addFormlet('child', ChildFormlet::class);
        });

        $form->build();

        $this->assertCount(2, $form->formlets());
        $this->assertCount(2, $form->formlets('child'));
        $this->assertCount(1, $form->formlets('main'));
        $this->assertInstanceOf(TestFormlet::class, $form->formlets('main')->first());
        $this->assertInstanceOf(ChildFormlet::class, $form->formlets('child')->first());

        $mainFormlet = $form->formlets('main')->first();
        $childFormlet = $form->formlets('child');

        $this->assertEquals('main[0][foo]', $mainFormlet->fields('foo')->first()->getInstanceName());
        $this->assertEquals('main[0][foo]', $form->fields('foo')->first()->getInstanceName());
        $this->assertEquals('child[0][name]', $childFormlet->get(0)->fields('name')->first()->getInstanceName());
        $this->assertEquals('child[1][name]', $childFormlet->get(1)->fields('name')->first()->getInstanceName());
    }

    /** @test */
    public function fields_can_be_filled_by_the_request()
    {

        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'name'));
            $form->add(new Input('text', 'person[name]'));
            $form->add(new Checkbox('agree', 'Yes', 'No'));
            $form->add(new Checkbox('novalue', 'Yes', 'No'));
            $form->add(new Radio('radio', [
              'foo' => 'bar',
              'bim' => 'baz'
            ]));
            $form->add(new CheckboxGroup('cb'), [
              1 => '1',
              2 => '2',
              4 => '3'
            ]);
        });

        $form->build();
        $fields = $form->fields();

        $this->assertEquals('foo', $fields->get('name')->getValue());
        $this->assertEquals('John', $fields->get('person[name]')->getValue());
        $this->assertEquals('Yes', $fields->get('agree')->getValue());
        $this->assertEquals('No', $fields->get('novalue')->getValue());
        $this->assertEquals('foo', $fields->get('radio')->getValue());
        $this->assertEquals([1, 2], $fields->get('cb')->getValue());
    }

    /** @test */
    public function should_populate_from_session()
    {
        // Takes precedence over request value
        $this->setOldInput([
          'main' => [
            [
              'name' => 'sessionVal'
            ]
          ]
        ]);

        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'name'));
            $form->add((new Input('text', 'name.with.dots'))->default('bar'));
        });
        $form->build();
        $fields = $form->fields();

        $this->assertEquals('sessionVal', $fields->get('name')->getValue());
        $this->assertEquals('bar', $fields->get('name.with.dots')->getValue());
    }

    /** @test */
    public function form_is_populated_from_a_model()
    {
        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'relation[key]'));
        });
        $form->model(['relation' => ['key' => 'attribute']])->build();
        $fields = $form->fields();

        $this->assertEquals('attribute', $fields->get('relation[key]')->getValue());
    }

    /** @test */
    public function form_is_populated_from_session_before_model()
    {

        $this->setOldInput([
          'main' => [
            ['foo' => 'bim']
          ]
        ]);

        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'foo'));
        });
        $form->model(['foo' => ['bar']])->build();
        $fields = $form->fields();

        $this->assertEquals('bim', $fields->get('foo')->getValue());
    }

    /** @test */
    public function form_can_repopulate_from_arrays_and_objects()
    {
        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'user[password]'));
        });
        $form->model(['user' => (object)['password' => 'apple']])->build();
        $fields = $form->fields();

        $this->assertEquals('apple', $fields->get('user[password]')->getValue());

        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'letters[1]'));
        });

        $form->model((object)['letters' => ['a', 'b', 'c']])->build();
        $fields = $form->fields();

        $this->assertEquals('b', $fields->get('letters[1]')->getValue());
    }

    /** @test */
    public function can_repopulate_select()
    {
        $this->setOldInput([
          'main' => [
            [
              'size' => 'M',
              'foo'  => ['multi' => ['L', 'S']]
            ]
          ]
        ]);
        $model = $this->createModel(['size' => ['key' => 'S'], 'other' => 'val']);
        $list = ['L' => 'Large', 'M' => 'Medium', 'S' => 'Small'];

        $form = $this->formlet(function (Formlet $form) use ($list) {
            $form->add(new Select('size', $list));
            $form->add((new Select('foo[multi]', $list))->multiple());
            $form->add((new Select('size[key]', $list))->multiple());
        });

        $form->model($model)->build();
        $fields = $form->fields();

        $this->assertEquals('M', $fields->get('size')->getValue());
        $this->assertEquals(['L', 'S'], $fields->get('foo[multi]')->getValue());
        $this->assertEquals('S', $fields->get('size[key]')->getValue());
    }

    /** @test */
    public function can_repopulate_checkbox()
    {
        $this->setOldInput([
          'main' => [
            ['check' => ['key' => 'yes']]
          ]
        ]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Checkbox('checkbox'));
            $form->add(new Checkbox('check[key]', 'yes'));
        });

        $form->build();
        $fields = $form->fields();

        $this->assertFalse($fields->get('checkbox')->getValue());
        $this->assertEquals('yes', $fields->get('check[key]')->getValue());
    }

    /** @test */
    public function can_repopulate_checkbox_group()
    {
        $this->setOldInput([
          'main' => [
            ['multicheck' => [1, 3]]
          ]
        ]);
        $list = [
          1 => 1,
          2 => 2,
          3 => 3
        ];

        $form = $this->formlet(function (Formlet $form) use ($list) {
            $form->add(new CheckboxGroup('multicheck', $list));
        });

        $form->build();
        $fields = $form->fields();

        $this->assertEquals([1, 3], $fields->get('multicheck')->getValue());
    }

    /** @test */
    public function can_repopulate_a_radio()
    {
        $this->setOldInput([
          'main' => [
            ['radio' => [1, 3]]
          ]
        ]);
        $list = [
          1 => 1,
          2 => 2,
          3 => 3
        ];

        $form = $this->formlet(function (Formlet $form) use ($list) {
            $form->add(new Radio('radio', $list));
        });

        $form->build();
        $fields = $form->fields();

        $this->assertEquals([1, 3], $fields->get('radio')->getValue());
    }

    /** @test */
    public function can_repopulate_checkbox_group_with_model_relation()
    {
        $mockModel2 = new StdClass();
        $mockModel2->id = 2;
        $mockModel3 = new StdClass();
        $mockModel3->id = 3;
        $model = $this->createModel(['items' => new Collection([$mockModel2, $mockModel3])]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new CheckboxGroup('items', [
              1 => 1,
              2 => 2,
              3 => 3,
              4 => 4
            ]));
        });

        $form->model($model)->build();
        $fields = $form->fields();

        $this->assertObjectHasAttribute('id', $fields->get('items')->getValue()->first());
        $this->assertContains('<input name="items[]" type="checkbox" checked="checked" value="2"/>',
          $fields->get('items')->render()->render());
    }

    private function formlet(\Closure $closure = null): TestFormlet
    {
        return $this->app->makeWith(TestFormlet::class, ['closure' => $closure]);
    }

    protected function createModel(array $data)
    {
        return new FormBuilderModelStub($data);
    }

    protected function setOldInput(array $data)
    {
        $this->session(['_old_input' => $data]);
    }

    protected function withRequest()
    {
        $this->request->merge([
          'name'   => 'foo',
          'person' => ['name' => 'John'],
          'agree'  => 'Yes',
          'radio'  => 'foo',
          'cb'     => [1, 2]
        ]);
    }

}

class TestFormlet extends Formlet
{

    protected $closure;

    public function __construct(\Closure $closure = null)
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

    public function persist()
    {
        return $this->post()->toArray();
    }

}

class ChildFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
    }

}

class FormBuilderModelStub
{
    protected $data;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = new self($val);
            }
            $this->data[$key] = $val;
        }
    }

    public function __get($key)
    {
        return $this->data[$key];
    }

    public function __isset($key)
    {
        return isset($this->data[$key]);
    }
}