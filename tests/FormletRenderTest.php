<?php

namespace Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RS\Form\Fields\Input;
use RS\Form\Formlet;

class FormletRenderTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();
        View::addLocation(__DIR__ . "/Fixtures/views");
    }

    public function getFormViews()
    {
        return [['form'],['formlet'],['formlets'],['fields']];
    }

    /**
     * @test
     * @dataProvider getFormViews
     */
    public function render_form_using_component($view)
    {
        $this->withoutExceptionHandling();

        Route::put('/users/{user}',function(){})->name('users.update');

        Route::get('/users/{user}/edit',function(RenderFormlet $form) use($view){
            return view($view,$form->route('users.update',['user'=>1])->build());
        })->name('users.edit');


        $this->get('/users/1/edit')
            ->assertStatus(200)
            ->assertSee('<form class="form" accept-charset="UTF-8" action="http://localhost/users/1" enctype="multipart/form-data" method="POST" >')
            ->assertSee('<input name="_method" type="hidden" value="PUT"/>')
            ->assertSee('<input name="_token" type="hidden" value="'.app('session')->token() .'"/>')
            ->assertSee('<input class="form_control" id="main[0][name]" name="main[0][name]" type="text" />')
            ->assertSee('<input class="form_control" id="main[0][email]" name="main[0][email]" type="email" />')
            ->assertSee('<input class="form_control" id="child[0][name]" name="child[0][name]" type="text" />');

    }




}

class RenderFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text','name'));
        $this->add(new Input('email','email'));

        $this->addFormlet('child',ChildRenderFormlet::class);
    }

}

class ChildRenderFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text','name'));
    }

}