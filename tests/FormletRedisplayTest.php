<?php

namespace RS\Form\Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use PHPUnit\Framework\Attributes\Test;
use RS\Form\Fields\Checkbox;
use RS\Form\Fields\CheckboxGroup;
use RS\Form\Fields\Input;
use RS\Form\Fields\Radio;
use RS\Form\Fields\Select;
use RS\Form\Fields\TextArea;
use RS\Form\Formlet;
use RS\Form\Tests\Fixtures\Formlets\ChildFormlet;
use RS\Form\Tests\Fixtures\Formlets\TestFormlet;
use RS\Form\Tests\Fixtures\Models\FormBuilderModelStub;

/**
 * When a form redisplays after a failed validation, the submitting formlet treats the
 * flashed old input as the whole truth: a field the user cleared stays cleared, and a
 * checkbox the user unticked stays unticked, instead of reverting to the model value.
 *
 * The submitting formlet is identified by the `_formlet` marker in old input matching
 * the formlet's own error bag name.
 *
 * Assertions are made against field state (getValue / isChecked / isCleared); the field
 * tests already cover that rendering follows that state.
 */
class FormletRedisplayTest extends TestCase
{
    use InteractsWithSession;

    // ----------------------------------------------------------------------------------
    // Key present in old input (even with a null value): the submitted value wins
    // ----------------------------------------------------------------------------------

    #[Test]
    public function a_cleared_text_field_stays_cleared_instead_of_reverting_to_the_model()
    {
        // ConvertEmptyStringsToNull turns a cleared '' into null before it is flashed.
        $this->submitted(['name' => null]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add((new Input('text', 'name'))->default('a default'));
        });
        $form->model($this->model(['name' => 'stored']))->build();

        $field = $form->field('name');
        $this->assertTrue($field->isCleared());
        $this->assertNull($field->getValue());
    }

    #[Test]
    public function a_cleared_textarea_stays_cleared()
    {
        $this->submitted(['bio' => null]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add((new TextArea('bio'))->default('a default'));
        });
        $form->model($this->model(['bio' => 'stored']))->build();

        $this->assertNull($form->field('bio')->getValue());
    }

    #[Test]
    public function a_cleared_select_stays_cleared()
    {
        $this->submitted(['colour' => null]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add((new Select('colour', ['red' => 'Red', 'blue' => 'Blue']))->default('red'));
        });
        $form->model($this->model(['colour' => 'blue']))->build();

        $this->assertNull($form->field('colour')->getValue());
    }

    #[Test]
    public function a_submitted_value_still_wins_over_the_model()
    {
        $this->submitted(['name' => 'typed']);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
        });
        $form->model($this->model(['name' => 'stored']))->build();

        $this->assertEquals('typed', $form->field('name')->getValue());
    }

    #[Test]
    public function a_checkbox_ticked_on_a_model_that_has_it_off_stays_ticked()
    {
        $this->submitted(['active' => '1']);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Checkbox('active'));
        });
        $form->model($this->model(['active' => false]))->build();

        $this->assertTrue($form->field('active')->isChecked());
    }

    // ----------------------------------------------------------------------------------
    // Key absent from old input: the field decides what its absence means
    // ----------------------------------------------------------------------------------

    #[Test]
    public function an_unticked_checkbox_stays_unticked_instead_of_reverting_to_the_model()
    {
        // An unchecked checkbox posts no key at all (GM-126).
        $this->submitted([]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add((new Checkbox('active'))->default(true));
        });
        $form->model($this->model(['active' => true]))->build();

        $field = $form->field('active');
        $this->assertFalse($field->isChecked());
        $this->assertFalse($field->getValue());
    }

    #[Test]
    public function an_unticked_checkbox_takes_its_configured_unchecked_value()
    {
        $this->submitted([]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Checkbox('active', 'yes', 'no'));
        });
        $form->model($this->model(['active' => 'yes']))->build();

        $this->assertEquals('no', $form->field('active')->getValue());
        $this->assertFalse($form->field('active')->isChecked());
    }

    #[Test]
    public function a_fully_unticked_checkbox_group_stays_empty_instead_of_reverting_to_the_model()
    {
        // Unticking every option posts no key at all.
        $this->submitted([]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add((new CheckboxGroup('items', [1 => 'One', 2 => 'Two', 3 => 'Three']))->default([3]));
        });
        $form->model($this->model(['items' => $this->related(1, 2)]))->build();

        $this->assertSame([], $form->field('items')->getValue());
    }

    #[Test]
    public function a_fully_cleared_multi_select_stays_empty_instead_of_reverting_to_the_model()
    {
        $this->submitted([]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add((new Select('tags', [1 => 'One', 2 => 'Two']))->multiple()->default([2]));
        });
        $form->model($this->model(['tags' => $this->related(1, 2)]))->build();

        $this->assertSame([], $form->field('tags')->getValue());
    }

    #[Test]
    public function a_partially_unticked_checkbox_group_keeps_the_surviving_options()
    {
        $this->submitted(['items' => [2]]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new CheckboxGroup('items', [1 => 'One', 2 => 'Two', 3 => 'Three']));
        });
        $form->model($this->model(['items' => $this->related(1, 2)]))->build();

        $this->assertEquals([2], $form->field('items')->getValue());
    }

    #[Test]
    public function an_absent_single_value_field_still_falls_back_to_the_model()
    {
        // A disabled input, a file input, or a field the view did not render never posts a
        // key. Its absence carries no "cleared" signal, so the legacy resolution applies.
        $this->submitted(['name' => 'typed']);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
            $form->add((new Input('text', 'locked'))->disabled());
        });
        $form->model($this->model(['name' => 'stored', 'locked' => 'kept']))->build();

        $this->assertEquals('kept', $form->field('locked')->getValue());
        $this->assertFalse($form->field('locked')->isCleared());
    }

    #[Test]
    public function an_absent_single_value_field_keeps_a_value_prepared_by_the_developer()
    {
        $this->submitted(['name' => 'typed']);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
            $form->add((new Input('text', 'other'))->setValue('prepared'));
        });
        $form->build();

        $this->assertEquals('prepared', $form->field('other')->getValue());
    }

    #[Test]
    public function an_absent_radio_still_falls_back_to_the_model()
    {
        // A user cannot untick a radio group, so an absent radio carries no "cleared" signal.
        $this->submitted(['name' => 'typed']);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
            $form->add(new Radio('size', ['s' => 'Small', 'l' => 'Large']));
        });
        $form->model($this->model(['name' => 'stored', 'size' => 'l']))->build();

        $this->assertEquals('l', $form->field('size')->getValue());
    }

    // ----------------------------------------------------------------------------------
    // Prefixes and child formlets
    // ----------------------------------------------------------------------------------

    #[Test]
    public function a_prefixed_formlet_is_identified_by_its_prefix()
    {
        $this->submitted(['pre:name' => null], 'pre');

        $form = $this->formlet(function (Formlet $form) {
            $form->setPrefix('pre');
            $form->add((new Input('text', 'name'))->default('a default'));
            $form->add((new Checkbox('active'))->default(true));
        });
        $form->model($this->model(['name' => 'stored', 'active' => true]))->build();

        $this->assertNull($form->field('name')->getValue());
        $this->assertFalse($form->field('active')->isChecked());
    }

    #[Test]
    public function child_formlets_inherit_the_submitted_state_from_the_root()
    {
        $this->submitted(['child' => [['name' => null]]]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
            $form->addFormlet('child', ChildFormlet::class);
        });
        $form->build();

        $child = $form->formlet('child');
        $this->assertNull($child->field('name')->getValue(), 'cleared child text field stays cleared');
        $this->assertFalse($child->field('active')->isChecked(), 'unticked child checkbox stays unticked');
    }

    // ----------------------------------------------------------------------------------
    // Isolation: only the form that was posted treats old input as authoritative
    // ----------------------------------------------------------------------------------

    #[Test]
    public function another_form_on_the_page_is_not_blanked_by_this_forms_validation_failure()
    {
        // Form "a" was posted with everything cleared and failed validation. Form "b" shares
        // the page; nothing of its own is in old input and it must keep its model values.
        $this->submitted(['a:name' => null], 'a');

        $other = $this->formlet(function (Formlet $form) {
            $form->setPrefix('b');
            $form->add(new Input('text', 'name'));
            $form->add(new Checkbox('active'));
            $form->add(new CheckboxGroup('items', [1 => 'One', 2 => 'Two']));
        });
        $other->model($this->model(['name' => 'stored', 'active' => true, 'items' => $this->related(1)]))->build();

        $this->assertEquals('stored', $other->field('name')->getValue());
        $this->assertTrue($other->field('active')->isChecked());
        $this->assertTrue($other->field('items')->getValue()->contains('id', 1));
    }

    #[Test]
    public function an_unprefixed_form_ignores_old_input_posted_by_a_prefixed_form()
    {
        $this->submitted(['name' => null], 'other');

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
            $form->add((new Checkbox('active'))->default(true));
        });
        $form->model($this->model(['name' => 'stored', 'active' => true]))->build();

        $this->assertEquals('stored', $form->field('name')->getValue());
        $this->assertTrue($form->field('active')->isChecked());
    }

    // ----------------------------------------------------------------------------------
    // Legacy fallback: without the marker, resolution is unchanged
    // ----------------------------------------------------------------------------------

    #[Test]
    public function old_input_from_a_form_that_is_not_a_formlet_is_ignored()
    {
        // A hand-written form on the same page failed validation and redirected back. Its
        // flashed input carries no _formlet marker, so this formlet must not read it as
        // "I was posted with everything cleared". (Checking hasOldInput() alone would.)
        $this->session(['_old_input' => ['name' => null]]);

        $form = $this->formlet(function (Formlet $form) {
            $form->add(new Input('text', 'name'));
            $form->add((new Checkbox('active'))->default(true));
        });
        $form->model($this->model(['name' => 'stored', 'active' => true]))->build();

        $this->assertEquals('stored', $form->field('name')->getValue());
        $this->assertFalse($form->field('name')->isCleared());
        $this->assertTrue($form->field('active')->isChecked());
    }

    #[Test]
    public function a_plain_edit_page_with_no_old_input_populates_from_the_model()
    {
        $form = $this->formlet(function (Formlet $form) {
            $form->add((new Input('text', 'name'))->default('a default'));
            $form->add(new Checkbox('active'));
            $form->add((new Select('tags', [1 => 'One', 2 => 'Two']))->multiple());
        });
        $form->model($this->model(['name' => 'stored', 'active' => true, 'tags' => $this->related(2)]))->build();

        $this->assertEquals('stored', $form->field('name')->getValue());
        $this->assertTrue($form->field('active')->isChecked());
        $this->assertTrue($form->field('tags')->getValue()->contains('id', 2));
    }

    #[Test]
    public function a_plain_create_page_with_no_old_input_uses_defaults()
    {
        $form = $this->formlet(function (Formlet $form) {
            $form->add((new Input('text', 'name'))->default('a default'));
            $form->add((new Checkbox('active'))->default(true));
            $form->add((new CheckboxGroup('items', [1 => 'One', 2 => 'Two']))->default([2]));
        });
        $form->build();

        $this->assertEquals('a default', $form->field('name')->getValue());
        $this->assertTrue($form->field('active')->isChecked());
        $this->assertEquals([2], $form->field('items')->getValue());
    }

    // ----------------------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------------------

    /**
     * Flash old input as a validation-failure redirect would, including the marker that
     * identifies the posted formlet.
     */
    protected function submitted(array $input, string $formlet = 'default'): void
    {
        $this->session(['_old_input' => ['_formlet' => $formlet] + $input]);
    }

    private function formlet(?\Closure $closure = null): TestFormlet
    {
        return $this->app->makeWith(TestFormlet::class, ['closure' => $closure]);
    }

    private function model(array $data = []): FormBuilderModelStub
    {
        return new FormBuilderModelStub($data);
    }

    /**
     * A related collection as an Eloquent relation would expose it: models keyed by id.
     */
    private function related(int ...$ids)
    {
        return collect($ids)->map(fn (int $id) => (object) ['id' => $id]);
    }
}
