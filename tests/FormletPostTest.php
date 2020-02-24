<?php

namespace RS\Form\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use RS\Form\Fields\Checkbox;
use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Input;
use RS\Form\Formlet;
use Symfony\Component\HttpFoundation\FileBag;

class FormletPostTest extends TestCase
{

    /** @var Request */
    protected $request;

    /** @var UploadedFile */
    protected $file;

    protected function setUp():void
    {
        parent::setUp();
        $this->request = $this->app['request'];

        $this->request->merge([
          'name'           => 'foo',
          'agree'          => 'Yes',
          'cb'             => [1, 2],
          'child'          => [['foo' => 'bar']],
          'prefix:country' => 'England'
        ]);
        $this->file = UploadedFile::fake()->create('test');

        $this->request->files = new FileBag([
          'child'=>[['file'=>$this->file]],
          'file' => $this->file
        ]);
    }

    /** @test */
    public function can_retrieve_posted_values()
    {

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
            $form->add(new Checkbox('agree', 'Yes', 'No'));
            $form->add(new Checkbox('foo', 'Yes', 'No'));
            $form->add(new CheckboxGroup('cb', [
              1 => 1,
              2 => 2,
              3 => 3
            ]));
            $form->add(new Input('file','file'));
            $form->addFormlet('child', ChildPostFormlet::class);
        });

        $form->build();

        $this->assertEquals([
          'name'  => 'foo',
          'agree' => 'Yes',
          'foo'   => 'No',
          'cb'    => [1, 2],
          'file'=> $this->file
        ], $form->postData()->all());

        $this->assertEquals('foo', $form->postData('name'));

        $this->assertEquals([
          'foo' => 'bar',
          'file'=> $this->file
        ], $form->formlet('child')->postData()->all());

        $this->assertEquals([
          'name'  => 'foo',
          'agree' => 'Yes',
          'foo'   => 'No',
          'cb'    => [1, 2],
          'child' => [['foo' => 'bar','file'  => $this->file]],
          'file'=> $this->file
        ], $form->allPostData()->toArray());
    }

    /** @test */
    public function can_retrieve_posted_values_for_a_prefixed_form()
    {

        $form = $this->formlet(function (Formlet $form) {
            $form->prefix = "prefix";
            $form->add(new Input('text', 'country'));
        });

        $form->build();

        $this->assertEquals([
          'country' => 'England'
        ], $form->postData()->all());

        $this->assertEquals('England', $form->postData('country'));

        $this->assertEquals([
          'country' => 'England'
        ], $form->allPostData()->toArray());
    }

    /** @test */
    public function can_store_with_a_valid_post()
    {
        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'name'));
        });

        $this->assertEquals(['name' => 'foo'], $form->store());
    }

    /** @test */
    public function store_throws_an_error_on_invalid_post()
    {

        Route::post('/test', function () {
            $form = $this->formlet(function ($form) {
                $form->add(new Input('text', 'name'));
            });
            $form->store();
        });

        $this->from('/test')
          ->post('/test', [
            'name' => ''
          ])
          ->assertRedirect('/test')
          ->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function can_update_with_a_valid_post()
    {
        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'name'));
        });

        $this->assertEquals(['name' => 'foo'], $form->update());
    }

    /** @test */
    public function update_throws_an_error_on_invalid_post()
    {

        Route::post('/test', function () {
            $form = $this->formlet(function ($form) {
                $form->add(new Input('text', 'name'));
            });
            $form->update();
        });

        $this->from('/test')
          ->post('/test', [
            'name' => ''
          ])
          ->assertRedirect('/test')
          ->assertSessionHasErrors(['name']);
    }

    private function formlet(\Closure $closure = null): Formlet
    {
        return $this->app->makeWith(PostFormlet::class, ['closure' => $closure]);
    }

}

class PostFormlet extends Formlet
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

    public function rules(): array
    {
        return [
          'name' => 'required'
        ];
    }

    public function persist()
    {
        return $this->postData()->all();
    }

    public function edit()
    {
        return $this->postData()->all();
    }

}

class ChildPostFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'foo'));
        $this->add(new Input('file', 'file'));
    }

}
