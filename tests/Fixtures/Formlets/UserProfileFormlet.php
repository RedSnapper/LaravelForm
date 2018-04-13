<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class UserProfileFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('email','email'));
        $this->relation('profile',ProfileFormlet::class);
    }

    public function persist()
    {
        
        $user = $this->model->create($this->postData()->all());

        $user->assignProfile($this->formlet('profile')->postData()->all());

    }

    public function edit()
    {
        $user = $this->model;
        $user->update($this->postData()->all());

        $user->assignProfile($this->formlet('profile')->postData()->all());

    }


}