<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Checkbox;
use RS\Form\Fields\Input;
use RS\Form\Formlet;

class TestProfileFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text','name'));
        $this->add(new Checkbox('active'));
    }


}