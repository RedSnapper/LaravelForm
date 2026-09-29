<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Checkbox;
use RS\Form\Fields\Input;
use RS\Form\Formlet;

class ChildFormlet extends Formlet
{
    public function prepare(): void
    {
        $this->add((new Input('text', 'name'))->default('a default'));
        $this->add((new Checkbox('active'))->default(true));
        $this->addFormlet('grandchild', GrandChildFormlet::class);
    }
}
