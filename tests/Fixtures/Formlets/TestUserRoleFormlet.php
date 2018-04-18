<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Input;
use RS\Form\Formlet;
use Tests\Fixtures\Models\TestRole;

class TestUserRoleFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('email', 'email'));
        $this->add(new CheckboxGroup('roles', TestRole::pluck('name', 'id')));
    }

    public function persist()
    {

        $user = $this->model->create($this->postData()->except('roles')->all());

        $user->roles()->sync($this->postData('roles'));
    }

}