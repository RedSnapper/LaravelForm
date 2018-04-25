<?php

namespace RS\Form\Tests\Fields;

trait RendersLabels
{
    /** @test */
    public function can_render_a_label_for_a_field()
    {
        $field = $this->getTestField('bim');
        $field->label('My Label');
        $rendered = $this->renderField($field);
        $this->assertContains('<label for="bim">My Label</label>',$rendered);
    }

    /** @test */
    public function will_not_render_a_label_if_one_is_not_set()
    {
        $field = $this->getTestField('bim');
        $rendered = $this->renderField($field);
        $this->assertNotContains('<label',$rendered);
    }
}