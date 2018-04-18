<?php

namespace Tests\Fixtures\Formlets;

use RS\Form\Fields\Input;
use RS\Form\Formlet;

class TestUserPostsFormlet extends Formlet
{

    public function prepare(): void
    {
        $this->add(new Input('email','email'));
        $this->relation('posts',TestPostFormlet::class,null,2);
    }

    public function persist(){
        $user = parent::persist();

        $this->formlets('posts')->each(function(Formlet $formlet) use($user){
            $user->posts()->create($formlet->postData()->all());
        });

    }

    public function edit()
    {
        parent::edit();

        $this->formlets('posts')->each(function(Formlet $formlet){
            $formlet->edit();
        });

    }

}