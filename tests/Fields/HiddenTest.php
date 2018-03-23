<?php

namespace Tests\Fields;
use RS\Form\Fields\Hidden;

class HiddenTest extends AbstractFieldTest
{
    
    protected function getTestField($name="foo"){
        return new Hidden($name);
    }

    /** @test */
    public function a_hidden_input_will_have_a_name_set()
    {
        $field = new Hidden('bim');
        $this->assertEquals('bim',$field->getName());
        $this->assertEquals('bim',$field->getInstanceName());
    }

    /** @test */
    public function a_hidden_input_will_have_a_type_attribute_set()
    {
        $field = new Hidden('bim');
        $this->assertEquals('hidden',$field->getAttribute('type'));
    }

    /** @test */
    public function a_hidden_input_will_have_a_view_set()
    {
        $field = new Hidden('text');
        $this->assertEquals('form.fields.hidden',$field->getView());
    }

}
