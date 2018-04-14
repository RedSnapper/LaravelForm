<?php

namespace RS\Form\Fields;

use Illuminate\Support\Collection;

class Checkbox extends AbstractField
{

    /**
     * The value of the checkbox when checked
     */
    protected $checked;

    /**
     * The value of the checkbox when not checked
     */
    protected $unchecked;

    protected $view = "form::fields.checkbox";
    protected $type = "checkable";

    public function __construct(string $name = null, $checked = true, $unchecked = false)
    {
        $this->attributes = collect(['type' => 'checkbox']);
        $this->setName($name);
        $this->checked = $checked;
        $this->unchecked = $unchecked;
    }

    public function getValue()
    {
        return is_null($this->value) ? $this->unchecked : $this->checked;
    }

    public function getHTMLValue()
    {
        return $this->checked;
    }

    public function getUnCheckedValue()
    {
        return $this->unchecked;
    }

    public function getCheckedValue()
    {
        return $this->checked;
    }

    /*
     * Is this checkbox checked
     * @return bool
     */
    public function isChecked(): bool
    {
        return !is_null($this->value) || !is_null($this->default);
    }

    protected function data(): Collection
    {
        $this->isChecked() ? $this->setAttribute('checked', 'checked') : $this->removeAttribute('checked');

        return parent::data();
    }

}