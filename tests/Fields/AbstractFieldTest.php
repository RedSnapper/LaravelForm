<?php

namespace Tests\Fields;
use RS\Form\Fields\AbstractField;
use Tests\TestCase;

class AbstractFieldTest extends TestCase
{
    
    protected function getTestField($name="foo"){
        return new TestField($name);
    }
    

    /** @test */
    public function a_field_has_a__name()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo",$field->getName());
        $this->assertEquals("foo",$field->getAttribute("name"));
        $field->setName("bar");
        $this->assertEquals("bar",$field->getName());
    }

    /** @test */
    public function can_set_an_instance_name_for_a_field()
    {
        $field = $this->getTestField("foo");
        $this->assertEquals("foo",$field->getInstanceName());
        $field->setInstanceName("bar");
        $this->assertEquals("bar",$field->getInstanceName());
        $this->assertEquals("foo",$field->getName());
        $this->assertEquals("bar",$field->getAttribute('name'));
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
        $field->setType("foo");
        $this->assertEquals("foo",$field->getType());
        $this->assertFalse($field->isCheckable());
        $field->setType("checkable");
        $this->assertEquals("checkable",$field->getType());
        $this->assertTrue($field->isCheckable());
    }

    /** @test */
    public function a_field_can_have_an_error_name()
    {
        $field = $this->getTestField();
        $field->setName("foo");
        $this->assertEquals("foo",$field->getErrorName());
        $field->setInstanceName("bar");
        $this->assertEquals("bar",$field->getErrorName());
        $field->setInstanceName("bim[]");
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
        $keys = $field->data()->keys();

        $this->assertContains('attributes',$keys);
        $this->assertContains('value',$keys);
        $this->assertContains('label',$keys);
        $this->assertContains('errorName',$keys);
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
    public function __construct($name)
    {
        $this->attributes = collect();
        $this->setName($name);
    }
}