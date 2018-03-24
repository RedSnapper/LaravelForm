<?php

namespace Tests\Fields;

trait RendersLabels
{
    /** @test */
    public function can_render_a_label_for_a_field()
    {
        $field = $this->getTestField('bim');
        $field->label('My Label');
        $rendered = $field->render()->render();
        $this->assertContains('<label class="control-label" for="bim">My Label</label>',$rendered);
    }

    /** @test */
    public function will_not_render_a_label_if_one_is_not_set()
    {
        $field = $this->getTestField('bim');
        $rendered = $field->render()->render();
        $this->assertNotContains('<label',$rendered);
    }
}