<?php

namespace RS\Form\Tests\Fields;

use Illuminate\Support\Collection;
use RS\Form\Fields\Choice;

class ChoiceTest extends AbstractFieldTest
{

    protected function getTestField($name = "foo")
    {
        return new Choice($name);
    }

    /** @test */
    public function a_field_can_have_a_placeholder()
    {
        $field = new Choice('text');
        $field->placeholder('bim');
        $field->build();
        $option = $field->getOptions()->first();

        $this->assertEquals('bim', $option->label);
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

        $field->build();

        $options = $field->getOptions();

        $this->assertCount(2, $options);
        $this->assertEquals('foo', $options->first()->value);
        $this->assertEquals('bar', $options->first()->label);
        $this->assertNull($options->first()->attributes->get('disabled'));

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
                'label' => "foo",
                'value' => "bar",
                'attributes' => ['disabled', ['data-foo' => 'bar']],
            ],
            'baz' => 'wibble'
        ]);

        $field->build();

        $option = $field->getOptions()->first();

        $this->assertEquals('bar', $option->value);
        $this->assertEquals('foo', $option->label);
        $this->assertEquals('disabled', $option->attributes->get('disabled'));
        $this->assertEquals('bar', $option->attributes->get('data-foo'));

        $option = $field->getOptions()->last();
        $this->assertEquals('baz', $option->value);
        $this->assertEquals('wibble', $option->label);

    }

    /** @test */
    public function choice_can_define_optgroups_using_minimal_syntax()
    {
        $field = new Choice('bim', [
            [
                'label' => "bim",
                'value' => [
                    'baz' => 'wibble'
                ],
            ]
        ]);

        $field->build();

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
                'label' => "bar",
                'value' => [
                    [
                        'label' => "foo",
                        'value' => "bar",
                        'attributes' => ['disabled', ['data-foo' => 'bar']],
                    ]
                ],
            ]
        ]);

        $field->build();

        $option = $field->getOptions()->first();
        $optGroupOptions = $option->options;

        $this->assertEquals('bar', $option->label);
        $this->assertInstanceOf(Collection::class, $optGroupOptions);

        $option = $optGroupOptions->first();
        $this->assertEquals('foo', $option->label);
        $this->assertEquals('bar', $option->value);
        $this->assertEquals('disabled', $option->attributes->get('disabled'));
        $this->assertEquals('bar', $option->attributes->get('data-foo'));

    }

    /** @test */
    public function a_choice_option_can_be_selected()
    {
        $field = new Choice('bim', [
            'foo' => 'bar',
            3 => 'baz',
            'bim'=>['wibble'=>'foo']
        ]);

        $field->setValue('foo');
        $field->build();
        $options = $field->getOptionsWithValues();
        $this->assertEquals("selected", $options->first()->attributes->get('selected'));
        $this->assertNull($options->get(1)->attributes->get('selected'));
        $this->assertNull($options->get(2)->options->first()->attributes->get('selected'));

        $field->setValue(['foo','3','wibble']);
        $options = $field->getOptionsWithValues();
        $this->assertEquals("selected", $options->first()->attributes->get('selected'));
        $this->assertEquals("selected", $options->get(1)->attributes->get('selected'));
        $this->assertEquals("selected", $options->get(2)->options->first()->attributes->get('selected'));
    }

}
