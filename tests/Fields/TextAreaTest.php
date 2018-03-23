<?php

namespace Tests\Fields;
use RS\Form\Fields\TextArea;

class TextAreaTest extends AbstractFieldTest
{
    
    protected function getTestField($name="foo"){
        return new TextArea($name);
    }

    /** @test */
    public function a_textarea_will_have_a_name_set()
    {
        $field = $this->getTestField("bim");
        $this->assertEquals('bim',$field->getName());
        $this->assertEquals('bim',$field->getInstanceName());
    }

    /** @test */
    public function an_input_will_have_a_view_set()
    {
        $field = $this->getTestField();
        $this->assertEquals('form.fields.textarea',$field->getView());
    }

    /** @test */
    public function can_set_rows_for_a_textarea()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('rows'));
        $field->rows(10);
        $this->assertEquals(10,$field->getAttribute('rows'));
    }

    /** @test */
    public function can_set_cols_for_a_textarea()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('cols'));
        $field->cols(10);
        $this->assertEquals(10,$field->getAttribute('cols'));
    }

}
