<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class TestUserForm extends Formlet
{

    public function prepare(): void
    {
        $this->prefix="prefix";
        $this->add(new Input('email','email'));
    }

}