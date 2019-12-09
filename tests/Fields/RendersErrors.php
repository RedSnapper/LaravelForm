<?php

namespace RS\Form\Tests\Fields;

trait RendersErrors
{
    /** @test */
    public function can_render_errors()
    {
        $field = $this->getTestField('bim');
        $field->setErrors(collect(['error1', 'error2']));
        $rendered = $this->renderField($field);
        $this->assertStringContainsString('error1', $rendered);
        $this->assertStringContainsString('error2', $rendered);
        $this->assertStringContainsString('is-invalid', $rendered);
    }
}