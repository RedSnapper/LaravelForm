<?php

namespace RS\Form\Fields;

class CheckboxGroup extends Choice
{

    protected $view = "form::fields.checkboxgroup";

    protected $selectedOption = "checked";

    protected $multiple = true;

    protected function getDefaultOptionAttributes():array{
        return [
          'name'=> $this->getAttribute('name'),
            'type'=> 'checkbox'
        ];
    }

}