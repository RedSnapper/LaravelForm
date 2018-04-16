<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Input;
use RS\Form\Formlet;
use Tests\Fixtures\Models\Role;

class UserPermissionForm extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('email', 'email'));
        $this->relation('permissions',UserPermissionFormlet::class);

    }



}