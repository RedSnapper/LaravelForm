<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\Radio;

class RadioTest extends AbstractFieldTest
{
    use RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new Radio($name, ['foo' => 'bar']);
    }

    #[Test]
    public function can_render_radio()
    {
        $field = new Radio('bim', [
          'foo' => 'bar',
          'bim' => 'baz',
        ]);
        $field->setValue('bim');

        $this->assertStringContainsString('<input class="form-check-input" name="bim" type="radio" value="foo"/>',
          $this->renderField($field));
        $this->assertStringContainsString('<input class="form-check-input" name="bim" type="radio" checked="checked" value="bim"/>',
          $this->renderField($field));
        $this->assertStringContainsString('bar',
          $this->renderField($field));
        $this->assertStringContainsString('baz',
          $this->renderField($field));
    }

    #[Test]
    public function a_field_can_have_a_placeholder()
    {
        $field = new Radio('text');
        $field->placeholder('bim');
        $field->build();
        $option = $field->getOptions()->first();

        $this->assertEquals('bim', $option->label);
    }

    #[Test]
    public function can_render_radio_attributes()
    {
        $field = new Radio('bim', [
          [
            'label'      => "bar",
            'value'      => "foo",
            'attributes' => ['disabled'],
          ]
        ]);

        $this->assertStringContainsString('<input class="form-check-input" name="bim" type="radio" disabled="disabled" value="foo"/>',
          $this->renderField($field));
        $this->assertStringContainsString('bar',
          $this->renderField($field));

        $field->setInstanceName("foo[0]bim");

        $this->assertStringContainsString('<input class="form-check-input" name="foo[0]bim" type="radio" disabled="disabled" value="foo"/>',
          $this->renderField($field));
    }

    #[Test]
    public function can_render_label()
    {
        $field = new Radio('foo');
        $this->assertStringNotContainsString('My Label',
          $this->renderField($field));
        $field->label('My Label');
        $this->assertStringContainsString('My Label',
          $this->renderField($field));
    }

}
