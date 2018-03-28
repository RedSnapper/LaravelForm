<?php

namespace Tests\Fields;

use RS\Form\Fields\CheckboxGroup;

class CheckboxGroupTest extends AbstractFieldTest
{
    use RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new CheckboxGroup($name);
    }

    /** @test */
    public function a_field_has_a__name()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo",$field->getName());
        $this->assertEquals("foo[]",$field->getAttribute("name"));
        $field->setName("bar");
        $this->assertEquals("bar",$field->getName());
        $this->assertEquals("bar[]",$field->getAttribute("name"));
    }

    /** @test */
    public function can_set_an_instance_name_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo",$field->getInstanceName());
        $field->setInstanceName("bar");
        $this->assertEquals("bar",$field->getInstanceName());
        $this->assertEquals("foo",$field->getName());
        $this->assertEquals("bar[]",$field->getAttribute('name'));
    }

    /** @test */
    public function will_set_an_id_value_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo[]",$field->getAttribute('id'));
        $field->setInstanceName("bar");
        $this->assertEquals("bar[]",$field->getAttribute('id'));
    }

    /** @test */
    public function can_render_a_checkbox_group()
    {
        $field = new CheckboxGroup('bim', [
          'foo' => 'bar',
          'bim' => 'baz',
          'bar' => 'wibble',
        ]);
        $field->setValue(['bim','bar']);

        $this->assertContains('<input name="bim[]" type="checkbox" value="foo"/>',
          $field->render()->render());
        $this->assertContains('<input name="bim[]" type="checkbox" checked="checked" value="bim"/>',
          $field->render()->render());
        $this->assertContains('<input name="bim[]" type="checkbox" checked="checked" value="bar"/>',
          $field->render()->render());
        $this->assertContains('bar',
          $field->render()->render());
        $this->assertContains('baz',
          $field->render()->render());
    }

    /** @test */
    public function can_render_checkbox_attributes()
    {
        $field = new CheckboxGroup('bim', [
          [
            'label'      => "bar",
            'value'      => "foo",
            'attributes' => ['disabled'],
          ]
        ]);

        $this->assertContains('<input name="bim[]" type="checkbox" disabled="disabled" value="foo"/>',
          $field->render()->render());
        $this->assertContains('bar',
          $field->render()->render());
    }

    /** @test */
    public function can_render_label()
    {
        $field = new CheckboxGroup('foo');
        $this->assertNotContains('My Label',
          $field->render()->render());
        $field->label('My Label');
        $this->assertContains('My Label',
          $field->render()->render());
    }

}
