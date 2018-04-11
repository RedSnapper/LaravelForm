<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class ProfileFormlet extends Formlet
{
    public $name = "profile";

    public function prepare(): void
    {
        $this->add(new Input('text','name'));
    }


}