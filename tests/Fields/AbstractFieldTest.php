<?php

namespace Tests\Fields;
use RS\Form\Fields\AbstractField;
use Tests\TestCase;

class AbstractFieldTest extends TestCase
{
    
    protected function getTestField(){
        return new TestField();
    }
    
    /** @test */
    public function can_set_a_name_for_a_field()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getName());
        $field->setName("foo");
        $this->assertEquals("foo",$field->getName());
        $field->setName("bar");
        $this->assertEquals("bar",$field->getName());
    }

    /** @test */
    public function can_set_a_label_for_a_field()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getLabel());
        $field->label("foo");
        $this->assertEquals("foo",$field->getLabel());
        $field->label("bar");
        $this->assertEquals("bar",$field->getLabel());
    }

    /** @test */
    public function can_set_a_view_for_a_field()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getLabel());
        $field->view("foo");
        $this->assertEquals("foo",$field->getView());
        $field->view("bar");
        $this->assertEquals("bar",$field->getView());
    }

    /** @test */
    public function can_set_a_default_value()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getDefault());
        $field->default("foo");
        $this->assertEquals("foo",$field->getDefault());
        $field->default("bar");
        $this->assertEquals("bar",$field->getDefault());
    }

    /** @test */
    public function can_check_if_a_field_is_checkable()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getType());
        $field->setType("foo");
        $this->assertEquals("foo",$field->getType());
        $this->assertFalse($field->isCheckable());
        $field->setType("checkable");
        $this->assertEquals("checkable",$field->getType());
        $this->assertTrue($field->isCheckable());
    }

    /** @test */
    public function can_set_a_fieldname_for_a_field()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getFieldName());
        $field->setName("foo");
        $this->assertEquals("foo",$field->getFieldName());
        $field->setFieldName("bar");
        $this->assertEquals("bar",$field->getFieldName());
        $this->assertEquals("foo",$field->getName());
    }

    /** @test */
    public function a_field_can_have_an_error_name()
    {
        $field = $this->getTestField();
        $field->setName("foo");
        $this->assertEquals("foo",$field->getErrorName());
        $field->setFieldName("bar");
        $this->assertEquals("bar",$field->getErrorName());
        $field->setFieldName("bim[]");
        $this->assertEquals("bim",$field->getErrorName());
    }

    /** @test */
    public function a_field_can_be_disabled()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('disabled'));
        $field->disabled();
        $this->assertEquals('disabled',$field->getAttribute('disabled'));
        $field->disabled(false);
        $this->assertNull($field->getAttribute('disabled'));
    }

    /** @test */
    public function a_field_can_be_required()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('required'));
        $field->required();
        $this->assertEquals('required',$field->getAttribute('required'));
        $field->required(false);
        $this->assertNull($field->getAttribute('required'));
    }

    /** @test */
    public function a_field_can_have_a_placeholder()
    {
        $field = $this->getTestField();
        $this->assertNull($field->getAttribute('placeholder'));
        $field->placeholder('foo');
        $this->assertEquals('foo',$field->getAttribute('placeholder'));
    }

    /** @test */
    public function a_field_will_by_default_set_the_attribute_name_as_the_value()
    {
        $field = $this->getTestField();
        $field->setAttribute('foo');
        $this->assertEquals('foo',$field->getAttribute('foo'));
        $field->setAttribute('foo','bar');
        $this->assertEquals('bar',$field->getAttribute('foo'));
    }

    /** @test */
    public function can_set_multiple_attributes_at_once()
    {
        $field = $this->getTestField();
        $field->setAttributes([
          'foo'=> 'foo',
          'bar'=> 'bar'
        ]);

        $this->assertEquals('foo',$field->getAttribute('foo'));
        $this->assertEquals('bar',$field->getAttribute('bar'));
    }
    /** @test */
    public function can_retrieve_all_data_about_a_field()
    {
        $field = $this->getTestField();
        $field->setName('email');
        $field->label('Email Address');
        $field->setFieldName('bim[]');
        $field->view('home');
        $field->setValue('john@example.com');
        $field->setAttribute('foo','bar');

        $this->assertEquals('bar',$field->data()['attributes']->get('foo'));
        $this->assertEquals('john@example.com',$field->data()['value']);
        $this->assertEquals('Email Address',$field->data()['label']);
        $this->assertEquals('bim[]',$field->data()['name']);
        $this->assertEquals('home',$field->data()['view']);
        $this->assertEquals('email',$field->data()['field']);
        $this->assertEquals('bim',$field->data()['errorName']);

    }

    /** @test */
    public function can_dynamically_add_attributes_to_a_field()
    {
        $field = $this->getTestField();
        $field->foo('bar');
        $this->assertEquals('bar',$field->getAttribute('foo'));
    }

}

class TestField extends AbstractField{
    public function __construct()
    {
        $this->attributes = collect();
    }
}