<?php

namespace RS\Form\Tests\Fields;

use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\AbstractField;
use RS\Form\Tests\TestCase;

class AbstractFieldTest extends TestCase
{

    protected function getTestField($name = "foo")
    {
        return new TestField($name);
    }

    #[Test]
    public function a_field_has_a__name()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo", $field->getName());
        $this->assertEquals("foo", $field->getAttribute("name"));
        $field->setName("bar");
        $this->assertEquals("bar", $field->getName());
    }

    #[Test]
    public function can_set_an_instance_name_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo", $field->getInstanceName());
        $field->setInstanceName("bar");
        $this->assertEquals("bar", $field->getInstanceName());
        $this->assertEquals("foo", $field->getName());
        $this->assertEquals("bar", $field->getAttribute('name'));
    }

    #[Test]
    public function can_be_set_to_be_a_multi_field()
    {
        $field = $this->getTestField("foo");
        $field->multiple(true);
        $this->assertEquals("foo[]", $field->getAttribute('name'));
        $this->assertEquals("foo", $field->getName());
    }

    #[Test]
    public function will_set_an_id_value_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo", $field->getAttribute('id'));
        $field->setInstanceName("bar");
        $this->assertEquals("bar", $field->getAttribute('id'));
    }

    #[Test]
    public function can_set_a_label_for_a_field()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getLabel());
        $field->label("foo");
        $this->assertEquals("foo", $field->getLabel());
        $field->label("bar");
        $this->assertEquals("bar", $field->getLabel());
    }

    #[Test]
    public function can_set_a_view_for_a_field()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getLabel());
        $field->view("foo");
        $this->assertEquals("foo", $field->getView());
        $field->view("bar");
        $this->assertEquals("bar", $field->getView());
    }

    #[Test]
    public function can_set_a_default_value()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getDefault());
        $field->default("foo");
        $this->assertEquals("foo", $field->getDefault());
        $field->default("bar");
        $this->assertEquals("bar", $field->getDefault());
    }

    #[Test]
    public function the_default_value_is_returned_when_no_value_is_set()
    {
        $field = $this->getTestField();

        $field->default('foo');
        $this->assertEquals('foo', $field->getValue());
        $this->assertEquals('foo', $field->getHTMLValue());

        $field->setValue('bar');
        $this->assertEquals('bar', $field->getValue());
        $this->assertEquals('bar', $field->getHTMLValue());

        $field->default('bim');
        $this->assertEquals('bar', $field->getValue());
        $this->assertEquals('bar', $field->getHTMLValue());

    }


    #[Test]
    public function a_field_can_have_an_error_name()
    {
        $field = $this->getTestField();
        $field->setName("foo");
        $this->assertEquals("foo", $field->getErrorName());
        $field->setInstanceName("bar");
        $this->assertEquals("bar", $field->getErrorName());
        $field->setInstanceName("bim[]");
        $this->assertEquals("bim", $field->getErrorName());
    }

    #[Test]
    public function a_field_can_be_disabled()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('disabled'));
        $this->assertFalse($field->isDisabled());
        $this->assertTrue($field->isActive());
        $field->disabled();
        $this->assertEquals('disabled', $field->getAttribute('disabled'));
        $this->assertTrue($field->isDisabled());
        $this->assertFalse($field->isActive());
        $field->disabled(false);
        $this->assertNull($field->getAttribute('disabled'));
        $this->assertFalse($field->isDisabled());
        $this->assertTrue($field->isActive());
    }



    #[Test]
    public function a_field_can_be_required()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('required'));
        $field->required();
        $this->assertEquals('required', $field->getAttribute('required'));
        $field->required(false);
        $this->assertNull($field->getAttribute('required'));
    }

    #[Test]
    public function a_field_can_have_a_placeholder()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('placeholder'));
        $field->placeholder('foo');
        $this->assertEquals('foo', $field->getAttribute('placeholder'));
    }

    #[Test]
    public function a_field_can_be_guarded()
    {
        $field = $this->getTestField();

        $field->setValue("foo");

        $field->guarded(true);
        $this->assertNull($field->getHTMLValue());
        $this->assertEquals("foo", $field->getValue());

        $field->default("bar");
        $this->assertEquals("bar", $field->getHTMLValue());
        $this->assertEquals("foo", $field->getValue());

        $field->guarded(false);
        $this->assertEquals("foo", $field->getHTMLValue());
        $this->assertEquals("foo", $field->getValue());
    }

    #[Test]
    public function a_field_will_by_default_set_the_attribute_name_as_the_value()
    {
        $field = $this->getTestField();
        $field->setAttribute('foo');
        $this->assertEquals('foo', $field->getAttribute('foo'));
        $field->setAttribute('foo', 'bar');
        $this->assertEquals('bar', $field->getAttribute('foo'));
    }

    #[Test]
    public function can_set_errors_for_a_field()
    {
        $field = $this->getTestField();
        $this->assertCount(0, $field->getErrors());
        $field->setErrors(collect(['error1', 'error2']));
        $this->assertCount(2, $field->getErrors());
        $this->assertEquals('error1', $field->getErrors()->first());
        $this->assertEquals('error2', $field->getErrors()->last());

    }

    #[Test]
    public function can_set_multiple_attributes_at_once()
    {
        $field = $this->getTestField();
        $field->setAttributes([
            'foo' => 'foo',
            'bar' => 'bar'
        ]);

        $this->assertEquals('foo', $field->getAttribute('foo'));
        $this->assertEquals('bar', $field->getAttribute('bar'));
    }

    #[Test]
    public function can_dynamically_add_attributes_to_a_field()
    {
        $field = $this->getTestField();
        $field->foo('bar');
        $this->assertEquals('bar', $field->getAttribute('foo'));
    }



}

class TestField extends AbstractField
{

    public function __construct($name)
    {
        $this->attributes = collect();
        $this->setName($name);
    }
}