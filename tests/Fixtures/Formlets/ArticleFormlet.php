<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Fields\Checkbox;
use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Input;
use RS\Form\Fields\Select;
use RS\Form\Formlet;

/**
 * An edit form with one field of each shape that can arrive "empty" from the browser, and
 * a required title so a submission can be made to fail validation.
 */
class ArticleFormlet extends Formlet
{
    public function prepare(): void
    {
        $this->add(new Input('text', 'title'));
        $this->add((new Input('text', 'subtitle'))->default('a default'));
        $this->add((new Select('colour', ['red' => 'Red', 'blue' => 'Blue']))->default('red'));
        $this->add((new Checkbox('active'))->default(true));
        $this->add((new Select('tags', [1 => 'One', 2 => 'Two']))->multiple());
        $this->add(new CheckboxGroup('items', [1 => 'One', 2 => 'Two', 3 => 'Three']));
    }

    public function rules(): array
    {
        return ['title' => 'required'];
    }
}
