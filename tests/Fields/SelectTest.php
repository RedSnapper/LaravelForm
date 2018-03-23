<?php

namespace Tests\Fields;

use RS\Form\Fields\Select;

class SelectTest extends AbstractFieldTest
{

    protected function getTestField($name = "foo")
    {
        return new Select($name);
    }

    /** @test */
    public function a_select_will_have_a_view_set()
    {
        $field = new Select('text');
        $this->assertEquals('form.fields.select',$field->getView());
    }

    /** @test */
    public function a_select_can_set_a_placeholder()
    {
        $field = new Select('text');
        $field->placeholder('bim');
        $option = $field->getOptions()->first();

        $this->assertEquals('bim',$option->label);
    }


    /** @test */
    public function a_field_can_have_a_placeholder()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('multiple'));
        $field->multiple();
        $this->assertEquals('multiple',$field->getAttribute('multiple'));
        $field->multiple(false);
        $this->assertNull($field->getAttribute('multiple'));
    }

}
