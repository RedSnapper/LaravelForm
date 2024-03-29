<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\Hidden;

class HiddenTest extends AbstractFieldTest
{
    
    protected function getTestField($name="foo"){
        return new Hidden($name);
    }

    #[Test]
    public function a_hidden_input_will_have_a_name_set()
    {
        $field = new Hidden('bim');
        $this->assertEquals('bim',$field->getName());
        $this->assertEquals('bim',$field->getInstanceName());
    }

    #[Test]
    public function will_set_an_id_value_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertNull($field->getAttribute('id'));
    }

    #[Test]
    public function a_hidden_input_will_have_a_type_attribute_set()
    {
        $field = new Hidden('bim');
        $this->assertEquals('hidden',$field->getAttribute('type'));
    }

    #[Test]
    public function can_render_a_value(){
        $field = new Hidden('bim');
        $field->setValue('baz');
        $rendered = $field->render()->render();
        $this->assertStringContainsString('<input autocomplete="off" name="bim" type="hidden" value="baz"/>',$rendered);
    }

}
