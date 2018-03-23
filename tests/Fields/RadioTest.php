<?php

namespace Tests\Fields;

use RS\Form\Fields\Radio;

class RadioTest extends AbstractFieldTest
{

    protected function getTestField($name = "foo")
    {
        return new Radio($name);
    }

    /** @test */
    public function a_select_will_have_a_view_set()
    {
        $field = new Radio('foo');
        $this->assertEquals('form.fields.radio',$field->getView());
    }

}
