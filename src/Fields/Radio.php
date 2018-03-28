<?php

namespace RS\Form\Fields;

class Radio extends Choice
{

    protected $view = "form::fields.radio";

    protected $selectedOption = "checked";

    protected function getDefaultOptionAttributes():array{
        return [
            'name'=> $this->getAttribute('name'),
            'type'=> 'radio'
        ];
    }

}