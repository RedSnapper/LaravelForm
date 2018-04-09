<?php

namespace Tests;

use Illuminate\Support\Collection;
use RS\Form\Fields\Input;
use RS\Form\Formlet;
use RS\Form\FormletView;
use RS\Form\FormletViewCollection;

class FormletViewCollectionTest extends TestCase
{
    /** @var Formlet */
    public $form;

    /** @var FormletViewCollection */
    public $viewCollection;

    public function setUp()
    {
        parent::setUp();
        $this->form = app(TestFormlet::class);
        $this->form->build();
        $this->viewCollection = new FormletViewCollection($this->form->formlets());
    }
    
    /** @test */
    public function can_get_formlets()
    {
        $this->assertInstanceOf(Collection::class,$this->viewCollection->get());
        $this->assertCount(1,$this->viewCollection->get('main'));
    }

    /** @test */
    public function can_get_first_child_of_a_formlet_collection()
    {
        $this->assertInstanceOf(FormletView::class,$this->viewCollection->first('main'));
        $this->assertInstanceOf(FormletView::class,$this->viewCollection->first('main.child'));
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
