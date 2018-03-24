<?php

namespace Tests\Fields;

use RS\Form\Fields\Radio;

class RadioTest extends AbstractFieldTest
{
    use RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new Radio($name);
    }

    /** @test */
    public function can_render_radio()
    {
        $field = new Radio('bim',[
            'foo' => 'bar',
            'bim' => 'baz',
        ]);
        $field->setValue('bim');

        $this->assertContains('<input name="bim" type="radio" value="foo"/>',
            $field->render()->render());
        $this->assertContains('<input name="bim" type="radio" checked="checked" value="bim"/>',
            $field->render()->render());
        $this->assertContains('bar',
            $field->render()->render());
        $this->assertContains('baz',
            $field->render()->render());

    }

    /** @test */
    public function can_render_radio_attributes()
    {
        $field = new Radio('bim',[[
            'label' => "bar",
            'value' => "foo",
            'attributes' => ['disabled'],
        ]]);

        $this->assertContains('<input name="bim" type="radio" disabled="disabled" value="foo"/>',
            $field->render()->render());
        $this->assertContains('bar',
            $field->render()->render());

    }

    /** @test */
    public function can_render_label()
    {
        $field = new Radio('foo');
        $this->assertNotContains('My Label',
            $field->render()->render());
        $field->label('My Label');
        $this->assertContains('My Label',
            $field->render()->render());
    }

}
