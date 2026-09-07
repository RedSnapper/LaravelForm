<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\Checkbox;

class CheckboxTest extends AbstractFieldTest
{
    use RendersErrors;

    protected function getTestField($name = "foo")
    {
        return new Checkbox($name);
    }

    #[Test]
    public function the_unchecked_value_is_returned_when_no_value_is_set()
    {
        $field = new Checkbox('foo', 'bim', 'baz');
        $this->assertEquals('baz', $field->getValue());
        $this->assertFalse($field->isChecked());

        $field->default('bim');
        $this->assertEquals('baz', $field->getValue());
        $this->assertTrue($field->isChecked());

        $field->setValue('wibble');
        $this->assertEquals('baz',$field->getValue());
        $this->assertFalse($field->isChecked());

        $field->setValue('bim');
        $this->assertEquals('bim',$field->getValue());
        $this->assertTrue($field->isChecked());

    }

    #[Test]
    public function the_default_value_is_returned_when_no_value_is_set()
    {
        $field = new Checkbox('foo', 'bim', 'baz');

        $this->assertEquals('baz', $field->getValue());
        $this->assertEquals('bim', $field->getHTMLValue());

        // Even when a default value is set the value of the checkbox
        // is still the unchecked value
        $field->default('bim');
        $this->assertEquals('baz', $field->getValue());
        $this->assertEquals('bim', $field->getHTMLValue());

    }

    // Overrides the inherited generic test: a checkbox's getValue() is always checked/unchecked
    // and getHTMLValue() is always the checked value, so the assertions are expressed via isChecked().
    #[Test]
    public function setting_a_null_value_still_falls_back_to_the_default()
    {
        $field = new Checkbox('foo', 'bim', 'baz');
        $field->default('bim');

        $field->setValue(null);

        $this->assertTrue($field->isDirty());
        $this->assertTrue($field->isChecked());
    }

    // Overrides the inherited generic test for the same reason.
    #[Test]
    public function a_cleared_value_is_not_replaced_by_the_default()
    {
        $field = new Checkbox('foo', 'bim', 'baz');
        $field->default('bim');
        $this->assertTrue($field->isChecked());

        $field->clearValue();

        $this->assertTrue($field->isDirty());
        $this->assertFalse($field->isChecked());
        $this->assertEquals('baz', $field->getValue());
        $this->assertStringNotContainsString('checked=', $this->renderField($field));
    }

    // Overrides the inherited generic test for the same reason.
    #[Test]
    public function a_cleared_value_ignores_a_default_set_afterwards()
    {
        $field = new Checkbox('foo', 'bim', 'baz');

        $field->clearValue();
        $field->default('bim');

        $this->assertFalse($field->isChecked());
    }

    // Overrides the inherited generic test for the same reason.
    #[Test]
    public function setting_a_value_after_clearing_restores_normal_behaviour()
    {
        $field = new Checkbox('foo', 'bim', 'baz');
        $field->default('bim');
        $field->clearValue();

        $field->setValue('bim');
        $this->assertTrue($field->isChecked());

        $field->setValue(null);
        $this->assertTrue($field->isChecked(), 'setValue(null) after a clear reverts to default semantics');
    }

    #[Test]
    public function a_field_can_be_guarded()
    {
        $field = new Checkbox('foo', 'bim', 'baz');
        $field->guarded(true);
        $this->assertEquals('bim', $field->getHTMLValue());
        $field->guarded(false);
        $this->assertEquals('bim', $field->getHTMLValue());
    }

    #[Test]
    public function a_checkbox_will_have_a_name_set()
    {
        $field = new Checkbox('foo');
        $this->assertEquals('foo', $field->getName());
        $this->assertEquals('foo', $field->getInstanceName());
    }

    #[Test]
    public function an_input_will_have_a_type_attribute_set()
    {
        $field = new Checkbox('bim');
        $this->assertEquals('checkbox', $field->getAttribute('type'));
    }

    #[Test]
    public function a_checkbox_will_have_default_checked_values_set()
    {
        $field = new Checkbox('foo');
        $this->assertTrue($field->getCheckedValue());
        $this->assertFalse($field->getUnCheckedValue());
    }

    #[Test]
    public function a_checkbox_can_set_checked_values()
    {
        $field = new Checkbox('foo', 'bar', 'bim');
        $this->assertEquals('bar', $field->getCheckedValue());
        $this->assertEquals('bim', $field->getUnCheckedValue());
    }

    #[Test]
    public function can_render()
    {
        $field = new Checkbox('bim', true);
        $rendered = $this->renderField($field);
        $this->assertStringContainsString('<input class="form-check-input" id="bim" name="bim" type="checkbox" value="1"/>', $rendered);

        $field->setValue(true);
        $rendered = $this->renderField($field);
        $this->assertStringContainsString('<input class="form-check-input" checked="checked" id="bim" name="bim" type="checkbox" value="1"/>', $rendered);

        $field->setValue(null);
        $rendered = $this->renderField($field);
        $this->assertStringContainsString('<input class="form-check-input" id="bim" name="bim" type="checkbox" value="1"/>', $rendered);

        $field->default(true);
        $rendered = $this->renderField($field);
        $this->assertStringContainsString('<input class="form-check-input" checked="checked" id="bim" name="bim" type="checkbox" value="1"/>', $rendered);

        $field->setValue(false);
        $rendered = $this->renderField($field);
        $this->assertStringContainsString('<input class="form-check-input" id="bim" name="bim" type="checkbox" value="1"/>', $rendered);
    }

    #[Test]
    public function can_render_a_label()
    {
        $field = new Checkbox('bim', true);
        $field->label('My Label');
        $rendered = $this->renderField($field);
        $this->assertStringContainsString('My Label', $rendered);
    }

}
