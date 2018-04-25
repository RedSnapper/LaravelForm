<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class TestPostFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text','name'));
    }


}