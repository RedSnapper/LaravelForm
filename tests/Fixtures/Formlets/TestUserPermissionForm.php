<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Input;
use RS\Form\Formlet;
use Tests\Fixtures\Models\TestRole;

class TestUserPermissionForm extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('email', 'email'));
        $this->relation('permissions',TestUserPermissionFormlet::class);

    }

    public function edit()
    {
        $user = parent::edit();

        $user->permissions()->sync($this->subscriptionData('permissions'));

    }

    public function persist()
    {
        $user = parent::persist();

        $user->permissions()->sync($this->subscriptionData('permissions'));

    }

}