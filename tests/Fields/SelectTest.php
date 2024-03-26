<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\Select;

class SelectTest extends AbstractFieldTest
{
    use RendersErrors, RendersLabels;

    protected function getTestField($name = "foo")
    {
        return new Select($name);
    }

    #[Test]
    public function can_be_a_multi_select()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('multiple'));
        $field->multiple();
        $this->assertEquals('multiple', $field->getAttribute('multiple'));
        $field->multiple(false);
        $this->assertNull($field->getAttribute('multiple'));
    }

    #[Test]
    public function a_field_can_have_a_placeholder()
    {
        $field = new Select('text');
        $field->placeholder('bim');
        $field->build();
        $option = $field->getOptions()->first();

        $this->assertEquals('bim', $option->label);
    }

    #[Test]
    public function can_render_select()
    {
        $field = new Select('bim', [
          'foo' => 'bar',
          'bim' => 'baz',
        ]);
        $field->setValue('bim');

        $this->assertStringContainsString('<select class="form-control" id="bim" name="bim">',
          $this->renderField($field));
        $this->assertStringContainsString('<option value="foo" >bar</option>',
          $this->renderField($field));
        $this->assertStringContainsString('<option value="bim" selected="selected">baz</option>',
          $this->renderField($field));
    }

    #[Test]
    public function can_render_a_multi_select()
    {
        $field = new Select('bim', [
          'foo'    => 'bar',
          'bim'    => 'baz',
          'wibble' => 'wibble',
        ]);
        $field->multiple(true);
        $field->setValue(['bim','wibble']);

        $this->assertStringContainsString('<select class="form-control" id="bim[]" multiple="multiple" name="bim[]">',
          $this->renderField($field));
        $this->assertStringContainsString('<option value="foo" >bar</option>',
          $this->renderField($field));
        $this->assertStringContainsString('<option value="bim" selected="selected">baz</option>',
          $this->renderField($field));
        $this->assertStringContainsString('<option value="wibble" selected="selected">wibble</option>',
          $this->renderField($field));
    }

    #[Test]
    public function can_render_option_attributes()
    {
        $field = new Select('bim', [
          [
            'label'      => "bar",
            'value'      => "foo",
            'attributes' => ['disabled', ['data-foo' => 'bar']],
          ]
        ]);

        $this->assertStringContainsString('<option value="foo" disabled="disabled" data-foo="bar">bar</option>',
          $this->renderField($field));
    }

    #[Test]
    public function can_render_optgroups()
    {
        $field = new Select('bim', [
          'foo' => ['bar' => 'bim']
        ]);
        $field->setValue('bar');

        $this->assertStringContainsString('<option value="bar" selected="selected">bim</option>',
          $this->renderField($field));
        $this->assertStringContainsString('<optgroup label="foo">',
          $this->renderField($field));
    }

}
