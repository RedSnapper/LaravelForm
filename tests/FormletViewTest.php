<?php

namespace Tests;

use RS\Form\Fields\Input;
use RS\Form\Formlet;
use RS\Form\FormletView;
use RS\Form\FormletViewCollection;

class FormletViewTest extends TestCase
{
    /** @var Formlet */
    public $form;

    /** @var FormletView */
    public $formletView;

    public function setUp()
    {
        parent::setUp();
        $form = app(ViewTestFormlet::class);
        $form->build();
        $this->form = $form;
        $this->formletView = new FormletView($form);
    }
    
    /** @test */
    public function can_get_child_formlets()
    {
        $this->assertInstanceOf(FormletViewCollection::class,$this->formletView->get());
        $this->assertCount(1,$this->formletView->get('main'));
        $this->assertCount(1,$this->formletView->first('main')->get('child'));
    }

    /** @test */
    public function can_get_first_child_of_a_formlet_collection()
    {
        $this->assertInstanceOf(FormletView::class,$this->formletView->first('main'));

        $grandChild = $this->formletView->first('main.child.grandchild');
        $this->assertInstanceOf(FormletView::class,$grandChild);
        $this->assertEquals('grandchild',$grandChild->field('grandchild')->getName());

    }

    /** @test */
    public function can_retrieve_the_fields_for_a_formlet()
    {
        $view = $this->formletView;
        $this->assertCount(1,$view->fields());
        $this->assertInstanceOf(Input::class,$view->field('name'));
    }


}

class ViewTestFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
        $this->addFormlet('child',ViewChildFormlet::class);
    }

    public function persist()
    {
        return $this->allPostData()->toArray();
    }

}

class ViewChildFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'child'));
        $this->addFormlet('grandchild',ViewGrandChildFormlet::class);
    }

}

class ViewGrandChildFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'grandchild'));
    }

}
