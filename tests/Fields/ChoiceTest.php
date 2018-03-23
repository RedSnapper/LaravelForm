<?php

namespace Tests\Fields;

use Illuminate\Support\Collection;
use RS\Form\Fields\Choice;

class ChoiceTest extends AbstractFieldTest
{

    protected function getTestField($name = "foo")
    {
        return new Choice($name);
    }

    /** @test */
    public function a_choice_field_will_have_a_name_set()
    {
        $field = new Choice('bim');
        $this->assertEquals('bim', $field->getName());
        $this->assertEquals('bim', $field->getInstanceName());
    }

    /** @test */
    public function a_choice_can_have_options_and_opt_groups()
    {
        $field = new Choice('bim', [
          'foo' => 'bar',
          'bim' => [
            'baz' => 'wibble'
          ]
        ]);

        $options = $field->getOptions();

        $this->assertCount(2, $field->data()->get('options'));
        $this->assertCount(2, $options);
        $this->assertEquals('foo', $options->first()->value);
        $this->assertEquals('bar', $options->first()->label);
        $this->assertFalse($options->first()->disabled);

        $optGroupOptions = $options->last()->options;

        $this->assertEquals('bim', $options->last()->label);
        $this->assertInstanceOf(Collection::class, $optGroupOptions);

        $this->assertEquals('baz', $optGroupOptions->first()->value);
        $this->assertEquals('wibble', $optGroupOptions->first()->label);
    }

    /** @test */
    public function a_choice_can_have_disabled_options()
    {
        $field = new Choice('name', [
          [
            'label'    => "foo",
            'value'    => "bar",
            'disabled' => true,
          ],
          'baz'=>'wibble'
        ]);

        $option = $field->getOptions()->first();

        $this->assertEquals('bar', $option->value);
        $this->assertEquals('foo', $option->label);
        $this->assertTrue($option->disabled);

        $option = $field->getOptions()->last();
        $this->assertEquals('baz', $option->value);
        $this->assertEquals('wibble', $option->label);


    }

    /** @test */
    public function choice_can_define_optgroups_using_minimal_syntax()
    {
        $field = new Choice('bim', [
          [
            'label'    => "bim",
            'value'    => [
              'baz'=> 'wibble'
            ],
          ]
        ]);

        $option = $field->getOptions()->first();
        $optGroupOptions = $option->options;

        $this->assertEquals('bim', $option->label);
        $this->assertInstanceOf(Collection::class, $optGroupOptions);

        $this->assertEquals('baz', $optGroupOptions->first()->value);
        $this->assertEquals('wibble', $optGroupOptions->first()->label);
    }

    /** @test */
    public function choice_can_define_optgroups_using_explicit_syntax()
    {
        $field = new Choice('bim', [
         [
            'label'    => "bar",
            'value'    => [
              [
                'label'    => "foo",
                'value'    => "bar",
                'disabled' => true
              ]
            ],
          ]
        ]);

        $option = $field->getOptions()->first();
        $optGroupOptions = $option->options;

        $this->assertEquals('bar', $option->label);
        $this->assertInstanceOf(Collection::class, $optGroupOptions);

        $this->assertEquals('foo', $optGroupOptions->first()->label);
        $this->assertEquals('bar', $optGroupOptions->first()->value);
        $this->assertTrue($optGroupOptions->first()->disabled);

    }

}
