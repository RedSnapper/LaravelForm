<?php

namespace Tests\Fields;
use RS\Form\Fields\Input;

class InputTest extends AbstractFieldTest
{
    
    protected function getTestField($name="foo"){
        return new Input($name,'foo');
    }

    /** @test */
    public function an_input_will_have_a_name_set()
    {
        $field = new Input('text','bim');
        $this->assertEquals('bim',$field->getName());
        $this->assertEquals('bim',$field->getInstanceName());
    }

    /** @test */
    public function an_input_will_have_a_type_attribute_set()
    {
        $field = new Input('text','bim');
        $this->assertEquals('text',$field->getAttribute('type'));
    }

    /** @test */
    public function an_input_will_have_a_view_set()
    {
        $field = new Input('text','bim');
        $this->assertEquals('form.fields.input',$field->getView());
    }

}
