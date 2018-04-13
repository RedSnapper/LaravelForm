<?php

namespace Tests\Fields;

use RS\Form\Fields\CheckboxGroup;

class CheckboxGroupTest extends AbstractFieldTest
{
    use RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new CheckboxGroup($name, ['foo' => 'bar']);
    }

    /** @test */
    public function a_field_has_a__name()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo", $field->getName());
        $this->assertEquals("foo[]", $field->getAttribute("name"));
        $field->setName("bar");
        $this->assertEquals("bar", $field->getName());
        $this->assertEquals("bar[]", $field->getAttribute("name"));
    }

    /** @test */
    public function can_set_an_instance_name_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo", $field->getInstanceName());
        $field->setInstanceName("bar");
        $this->assertEquals("bar", $field->getInstanceName());
        $this->assertEquals("foo", $field->getName());
        $this->assertEquals("bar[]", $field->getAttribute('name'));
    }

    /** @test */
    public function will_set_an_id_value_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo[]", $field->getAttribute('id'));
        $field->setInstanceName("bar");
        $this->assertEquals("bar[]", $field->getAttribute('id'));
    }

    /** @test */
    public function can_render_a_checkbox_group()
    {
        $field = new CheckboxGroup('bim', [
            'foo' => 'bar',
            'bim' => 'baz',
            'bar' => 'wibble',
        ]);
        $field->setValue(['bim', 'bar']);

        $this->assertContains('<input class="form-check-input" name="bim[]" type="checkbox" value="foo"/>',
            $this->renderField($field));
        $this->assertContains('<input class="form-check-input" name="bim[]" type="checkbox" checked="checked" value="bim"/>',
            $this->renderField($field));
        $this->assertContains('<input class="form-check-input" name="bim[]" type="checkbox" checked="checked" value="bar"/>',
            $this->renderField($field));
        $this->assertContains('bar',
            $this->renderField($field));
        $this->assertContains('baz',
            $this->renderField($field));
    }

    /** @test */
    public function can_render_checkbox_attributes()
    {
        $field = new CheckboxGroup('bim', [
            [
                'label' => "bar",
                'value' => "foo",
                'attributes' => ['disabled'],
            ]
        ]);

        $this->assertContains('<input class="form-check-input" name="bim[]" type="checkbox" disabled="disabled" value="foo"/>',
            $this->renderField($field));
        $this->assertContains('bar',
            $this->renderField($field));
    }

    /** @test */
    public function can_render_label()
    {
        $field = new CheckboxGroup('foo');
        $this->assertNotContains('My Label',
            $this->renderField($field));
        $field->label('My Label');
        $this->assertContains('My Label',
            $this->renderField($field));
    }

}
