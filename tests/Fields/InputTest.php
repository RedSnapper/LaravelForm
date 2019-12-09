<?php

namespace RS\Form\Tests\Fields;

use RS\Form\Fields\Input;

class InputTest extends AbstractFieldTest
{
    use RendersErrors, RendersLabels;

    protected function getTestField($name = "foo")
    {
        return new Input('text', $name);
    }

    /** @test */
    public function an_input_will_have_a_name_set()
    {
        $field = new Input('text', 'bim');
        $this->assertEquals('bim', $field->getName());
        $this->assertEquals('bim', $field->getInstanceName());
    }

    /** @test */
    public function an_input_will_have_a_type_attribute_set()
    {
        $field = new Input('text', 'bim');
        $this->assertEquals('text', $field->getAttribute('type'));
    }

    /** @test */
    public function do_not_populate_input_of_type_password_or_file()
    {
        $field = new Input('password', 'foo');
        $field->setValue("bar");
        $this->assertEquals(null, $field->getHTMLValue());
        $this->assertEquals("bar", $field->getValue());

        $field = new Input('file', 'foo');
        $field->setValue("bar");
        $this->assertEquals(null, $field->getHTMLValue());
        $this->assertEquals("bar", $field->getValue());
    }

    /** @test */
    public function can_render_attributes()
    {
        $field = new Input('text', 'bim');
        $rendered = $field->render()->render();
        $this->assertStringContainsString('<input class="form-control" id="bim" name="bim" type="text" />', $rendered);
    }

    /** @test */
    public function can_render_a_value()
    {
        $field = new Input('text', 'bim');
        $field->setValue('baz');
        $rendered = $field->render()->render();
        $this->assertStringContainsString('<input class="form-control" id="bim" name="bim" type="text" value="baz"/>', $rendered);
    }

    /** @test */
    public function file_input_type_multiple_method_adds_multiple_attribute()
    {
        $field = new Input('file', 'bim');
        $field->multiple();
        $rendered = $field->render()->render();
        $this->assertStringContainsString('<input class="form-control" id="bim[]" multiple="multiple" name="bim[]" type="file" />',
          $rendered);
    }

}
