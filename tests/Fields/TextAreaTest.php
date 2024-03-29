<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\TextArea;

class TextAreaTest extends AbstractFieldTest
{
    use RendersLabels, RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new TextArea($name);
    }

    #[Test]
    public function a_textarea_will_have_a_name_set()
    {
        $field = $this->getTestField("bim");
        $this->assertEquals('bim', $field->getName());
        $this->assertEquals('bim', $field->getInstanceName());
    }

    #[Test]
    public function can_set_rows_for_a_textarea()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('rows'));
        $field->rows(10);
        $this->assertEquals(10, $field->getAttribute('rows'));
    }

    #[Test]
    public function can_set_cols_for_a_textarea()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('cols'));
        $field->cols(10);
        $this->assertEquals(10, $field->getAttribute('cols'));
    }

    #[Test]
    public function can_render_a_textarea()
    {
        $field = new TextArea('bim');
        // Text Areas should not have any extra space characters which have not been set from the value
        $this->assertStringContainsString('<textarea class="form-control" id="bim" name="bim"></textarea>', $field->render()->render());
        $field->setValue("Eggs & Sausage");
        $this->assertStringContainsString('<textarea class="form-control" id="bim" name="bim">Eggs &amp; Sausage</textarea>', $field->render()->render());
    }

}
