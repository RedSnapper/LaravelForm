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
        $this->form = app(TestFormlet::class);
        $this->formletView = new FormletView($this->form);
    }
    
    /** @test */
    public function can_get_child_formlets()
    {
        $this->assertInstanceOf(FormletViewCollection::class,$this->formletView->get());
        $this->assertCount(1,$this->formletView->get('child'));
    }

    /** @test */
    public function can_get_first_child_of_a_formlet_collection()
    {
        $this->assertInstanceOf(FormletView::class,$this->formletView->first('child'));

        $grandChild = $this->formletView->first('child.grandchild');
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

class TestFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
        $this->addFormlet('child',ChildFormlet::class);
    }

    public function persist()
    {
        return $this->allPostData()->toArray();
    }

}

class ChildFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'child'));
        $this->addFormlet('grandchild',GrandChildFormlet::class);
    }

}

class GrandChildFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'grandchild'));
    }

}
