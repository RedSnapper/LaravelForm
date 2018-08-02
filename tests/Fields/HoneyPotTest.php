<?php

namespace RS\Form\Tests\Fields;

use RS\Form\Fields\HoneyPot;

class HoneyPotTest extends AbstractFieldTest
{

    protected function getTestField($name = "foo")
    {
        return new HoneyPot($name);
    }

    /** @test */
    public function a_honey_pot_will_have_a_name_set()
    {
        $field = new HoneyPot('bim');
        $this->assertEquals('bim', $field->getName());
        $this->assertEquals('bim', $field->getInstanceName());
    }

    /** @test */
    public function will_set_an_id_value_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertNull($field->getAttribute('id'));
    }

    /** @test */
    public function a_honey_pot_will_have_a_type_attribute_set()
    {
        $field = new HoneyPot('bim');
        $this->assertEquals('text', $field->getAttribute('type'));
    }

    /** @test */
    public function can_render_a_value()
    {
        $field = new Honeypot('bim');
        $field->setValue('baz');
        $rendered = $field->render()->render();
        $this->assertContains('<input autocomplete="off" name="bim" type="text" value="baz"/>', $rendered);
    }

}
