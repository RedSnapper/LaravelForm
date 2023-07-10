<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class TestUserForm extends Formlet
{


    public function __construct()
    {
        $this->setPrefix('prefix');
    }

    public function prepare(): void
    {
        $this->add(new Input('email','email'));
    }

}