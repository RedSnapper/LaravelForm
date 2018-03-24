<?php

namespace Tests\Fields;

trait RendersErrors
{
    /** @test */
    public function can_render_errors()
    {
        $field = $this->getTestField('bim');
        $field->setErrors(collect(['error1','error2']));
        $rendered = $field->render()->render();
        $this->assertContains('error1',$rendered);
        $this->assertContains('error2',$rendered);
        $this->assertContains('has-error',$rendered);
    }
}