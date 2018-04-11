<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class UserFormlet extends Formlet
{
    public $name = "user";

    public function prepare(): void
    {
        $this->add(new Input('email','email'));
    }

}