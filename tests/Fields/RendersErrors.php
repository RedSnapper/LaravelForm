<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;

trait RendersErrors
{
    #[Test]
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