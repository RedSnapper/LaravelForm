<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Checkbox;
use RS\Form\Fields\Input;
use RS\Form\Formlet;
use RS\Form\Tests\Fixtures\Models\TestUser;

class TestProfileUserFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('text', 'name'));
        $this->add(new Checkbox('active'));
        $this->relation('user', TestUserForm::class);
    }

    public function persist()
    {

        $user = TestUser::create($this->formlet('user')->postData()->all());

        $user->assignProfile($this->postData()->all());
    }

    public function edit()
    {
        $user = $this->model;
        $user->update($this->postData()->all());

        $this->formlet('user')->edit();
    }

}