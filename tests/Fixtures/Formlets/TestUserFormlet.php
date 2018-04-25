<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class TestUserFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('email','email'));
    }

}