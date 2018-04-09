<?php

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RS\Form\Fields\Checkbox;
use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Input;
use RS\Form\Formlet;

class FormletPostTest extends TestCase
{

    /** @var Request */
    protected $request;

    //TODO Test default persist and edit methods

    protected function setUp()
    {
        parent::setUp();
        $this->request = $this->app['request'];

        $this->request->merge([
          'main' => [
            [
              'name'  => 'foo',
              'agree' => 'Yes',
              'cb'    => [1, 2]
            ]
          ]
        ]);
    }

    /** @test */
    public function can_retrieve_posted_values()
    {

        $form = $this->formlet(function ($form) {
            $form->add(new Input('text', 'name'));
            $form->add(new Checkbox('agree', 'Yes', 'No'));
            $form->add(new Checkbox('foo', 'Yes', 'No'));
            $form->add(new CheckboxGroup('cb', [
              1 => 1,
              2 => 2,
              3 => 3
            ]));
        });

        $this->assertEquals([
          'main' => [
            [
              'name'  => 'foo',
              'agree' => 'Yes',
              'foo'   => 'No',
              'cb'    => [1, 2]
            ]
          ]
        ],$form->allPostData()->toArray());
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
            ['main'=>[['name' => '']]]
          ])
          ->assertRedirect('/test')
          ->assertSessionHasErrors(['main.0.name']);
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
            ['main'=>[['name' => '']]]
          ])
          ->assertRedirect('/test')
          ->assertSessionHasErrors(['main.0.name']);
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
        return $this->postData()->toArray();
    }

    public function edit()
    {
        return $this->postData()->toArray();
    }

}