<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\CheckboxGroup;

class CheckboxGroupTest extends AbstractFieldTest
{
    use RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new CheckboxGroup($name, ['foo' => 'bar']);
    }

    #[Test]
    public function a_field_has_a__name()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo", $field->getName());
        $this->assertEquals("foo[]", $field->getAttribute("name"));
        $field->setName("bar");
        $this->assertEquals("bar", $field->getName());
        $this->assertEquals("bar[]", $field->getAttribute("name"));
    }

    #[Test]
    public function can_set_an_instance_name_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo", $field->getInstanceName());
        $field->setInstanceName("bar");
        $this->assertEquals("bar", $field->getInstanceName());
        $this->assertEquals("foo", $field->getName());
        $this->assertEquals("bar[]", $field->getAttribute('name'));
    }

    #[Test]
    public function will_set_an_id_value_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo[]", $field->getAttribute('id'));
        $field->setInstanceName("bar");
        $this->assertEquals("bar[]", $field->getAttribute('id'));
    }

    #[Test]
    public function can_render_a_checkbox_group()
    {
        $field = new CheckboxGroup('bim', [
            'foo' => 'bar',
            'bim' => 'baz',
            'bar' => 'wibble',
        ]);
        $field->setValue(['bim', 'bar']);

        $this->assertStringContainsString('<input class="form-check-input" name="bim[]" type="checkbox" value="foo"/>',
            $this->renderField($field));
        $this->assertStringContainsString('<input class="form-check-input" name="bim[]" type="checkbox" checked="checked" value="bim"/>',
            $this->renderField($field));
        $this->assertStringContainsString('<input class="form-check-input" name="bim[]" type="checkbox" checked="checked" value="bar"/>',
            $this->renderField($field));
        $this->assertStringContainsString('bar',
            $this->renderField($field));
        $this->assertStringContainsString('baz',
            $this->renderField($field));

        $field->setInstanceName("foo[0]bim");

        $this->assertStringContainsString('<input class="form-check-input" name="foo[0]bim[]" type="checkbox" checked="checked" value="bar"/>',
          $this->renderField($field));

    }

    #[Test]
    public function can_render_checkbox_attributes()
    {
        $field = new CheckboxGroup('bim', [
            [
                'label' => "bar",
                'value' => "foo",
                'attributes' => ['disabled'],
            ]
        ]);

        $this->assertStringContainsString('<input class="form-check-input" name="bim[]" type="checkbox" disabled="disabled" value="foo"/>',
            $this->renderField($field));
        $this->assertStringContainsString('bar',
            $this->renderField($field));
    }

    #[Test]
    public function can_render_label()
    {
        $field = new CheckboxGroup('foo');
        $this->assertStringNotContainsString('My Label',
            $this->renderField($field));
        $field->label('My Label');
        $this->assertStringContainsString('My Label',
            $this->renderField($field));
    }

    #[Test]
    public function a_field_can_have_a_placeholder()
    {
        $field = new CheckboxGroup('text');
        $field->placeholder('bim');
        $field->build();
        $option = $field->getOptions()->first();

        $this->assertEquals('bim', $option->label);
    }

}
