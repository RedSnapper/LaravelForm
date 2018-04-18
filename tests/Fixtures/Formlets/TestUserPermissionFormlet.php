<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\Checkbox;
use RS\Form\Fields\Input;
use RS\Form\Formlet;


class TestUserPermissionFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Checkbox('id',$this->related->id));
        $this->add(new Input('text', 'color'));
    }



}