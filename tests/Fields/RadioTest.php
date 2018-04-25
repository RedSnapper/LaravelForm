<?php

namespace RS\Form\Tests\Fields;

use RS\Form\Fields\Radio;

class RadioTest extends AbstractFieldTest
{
    use RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new Radio($name, ['foo' => 'bar']);
    }

    /** @test */
    public function can_render_radio()
    {
        $field = new Radio('bim', [
          'foo' => 'bar',
          'bim' => 'baz',
        ]);
        $field->setValue('bim');

        $this->assertContains('<input class="form-check-input" name="bim" type="radio" value="foo"/>',
          $this->renderField($field));
        $this->assertContains('<input class="form-check-input" name="bim" type="radio" checked="checked" value="bim"/>',
          $this->renderField($field));
        $this->assertContains('bar',
          $this->renderField($field));
        $this->assertContains('baz',
          $this->renderField($field));
    }

    /** @test */
    public function can_render_radio_attributes()
    {
        $field = new Radio('bim', [
          [
            'label'      => "bar",
            'value'      => "foo",
            'attributes' => ['disabled'],
          ]
        ]);

        $this->assertContains('<input class="form-check-input" name="bim" type="radio" disabled="disabled" value="foo"/>',
          $this->renderField($field));
        $this->assertContains('bar',
          $this->renderField($field));

        $field->setInstanceName("foo[0]bim");

        $this->assertContains('<input class="form-check-input" name="foo[0]bim" type="radio" disabled="disabled" value="foo"/>',
          $this->renderField($field));
    }

    /** @test */
    public function can_render_label()
    {
        $field = new Radio('foo');
        $this->assertNotContains('My Label',
          $this->renderField($field));
        $field->label('My Label');
        $this->assertContains('My Label',
          $this->renderField($field));
    }

}
