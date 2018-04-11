<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class UserProfileFormlet extends Formlet
{
    public $name = "user";

    public function prepare(): void
    {
        $this->add(new Input('email','email'));
        $this->relation('profile',ProfileFormlet::class);
    }

    public function persist()
    {
        
        $user = $this->model->create($this->postData());

        $user->assignProfile($this->formlets('user')->first()->formlets('profile')->first()->postData());

    }


}