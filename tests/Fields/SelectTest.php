<?php

namespace Tests\Fields;

use RS\Form\Fields\Select;

class SelectTest extends AbstractFieldTest
{
    use RendersErrors,RendersLabels;

    protected function getTestField($name = "foo")
    {
        return new Select($name);
    }

    /** @test */
    public function a_select_can_set_a_placeholder()
    {
        $field = new Select('text');
        $field->placeholder('bim');
        $option = $field->getOptions()->first();

        $this->assertEquals('bim',$option->label);
    }


    /** @test */
    public function a_field_can_have_a_placeholder()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('multiple'));
        $field->multiple();
        $this->assertEquals('multiple',$field->getAttribute('multiple'));
        $field->multiple(false);
        $this->assertNull($field->getAttribute('multiple'));
    }

    /** @test */
    public function can_render_select()
    {
        $field = new Select('bim',[
            'foo' => 'bar',
            'bim' => 'baz',
        ]);
        $field->setValue('bim');

        $this->assertContains('<select class="form_control" id="bim" name="bim">',
            $field->render()->render());
        $this->assertContains('<option value="foo" >bar</option>',
            $field->render()->render());
        $this->assertContains('<option value="bim" selected="selected">baz</option>',
            $field->render()->render());
    }

    /** @test */
    public function can_render_option_attributes()
    {
        $field = new Select('bim',[[
            'label' => "bar",
            'value' => "foo",
            'attributes' => ['disabled', ['data-foo' => 'bar']],
        ]]);

        $this->assertContains('<option value="foo" disabled="disabled" data-foo="bar">bar</option>',
            $field->render()->render());
    }

    /** @test */
    public function can_render_optgroups()
    {
        $field = new Select('bim',[
            'foo' => ['bar'=>'bim']
        ]);
        $field->setValue('bar');

        $this->assertContains('<option value="bar" selected="selected">bim</option>',
            $field->render()->render());
        $this->assertContains('<optgroup label="foo">',
            $field->render()->render());
    }


}
