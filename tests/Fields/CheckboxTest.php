<?php

namespace Tests\Fields;
use RS\Form\Fields\Checkbox;

class CheckboxTest extends AbstractFieldTest
{
    
    protected function getTestField($name="foo"){
        return new Checkbox($name);
    }

    /** @test */
    public function a_checkbox_will_have_a_name_set()
    {
        $field = new Checkbox('foo');
        $this->assertEquals('foo',$field->getName());
        $this->assertEquals('foo',$field->getInstanceName());
    }

    /** @test */
    public function a_checkbox_type_will_be_checkable()
    {
        $field = new Checkbox('foo');
        $this->assertTrue($field->isCheckable());
    }

    /** @test */
    public function a_checkbox_will_have_a_view_set()
    {
        $field = new Checkbox('foo');
        $this->assertEquals('form.fields.checkbox',$field->getView());
    }

    /** @test */
    public function a_checkbox_will_have_default_checked_values_set()
    {
        $field = new Checkbox('foo');
        $this->assertTrue($field->getCheckedValue());
        $this->assertFalse($field->getUnCheckedValue());
    }

    /** @test */
    public function a_checkbox_can_set_checked_values()
    {
        $field = new Checkbox('foo','bar','bim');
        $this->assertEquals('bar',$field->getCheckedValue());
        $this->assertEquals('bim',$field->getUnCheckedValue());
    }

    /** @test */
    public function a_checkbox_value_is_always_the_same_as_the_checked_value(){
        $field = new Checkbox('foo','bar','bim');
        $this->assertEquals('bar',$field->getValue());
        $field->setValue('foo');
        $this->assertEquals('bar',$field->getValue());
    }


}
