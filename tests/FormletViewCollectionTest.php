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
        $this->form = app(CollectionTestFormlet::class);
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

class CollectionTestFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
        $this->addFormlet('child',CollectionChildFormlet::class);
    }

    public function persist()
    {
        return $this->allPostData()->toArray();
    }

}

class CollectionChildFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'child'));
        $this->addFormlet('grandchild',CollectionGrandChildFormlet::class);
    }

}

class CollectionGrandChildFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'grandchild'));
    }

}
