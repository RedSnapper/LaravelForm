<?php

namespace Tests\Fields;
use RS\Form\Fields\Input;

class InputTest extends AbstractFieldTest
{
    use RendersErrors,RendersLabels;
    
    protected function getTestField($name="foo"){
        return new Input('text',$name);
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
    public function can_render_attributes(){
        $field = new Input('text','bim');
        $rendered = $field->render()->render();
        $this->assertContains('<input class="form_control" id="bim" name="bim" type="text" />',$rendered);
    }

    /** @test */
    public function can_render_a_value(){
        $field = new Input('text','bim');
        $field->setValue('baz');
        $rendered = $field->render()->render();
        $this->assertContains('<input class="form_control" id="bim" name="bim" type="text" value="baz"/>',$rendered);
    }

}
