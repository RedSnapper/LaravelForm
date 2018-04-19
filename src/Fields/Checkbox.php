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

        $checked =  !is_null($this->value) && (string)$this->value === (string)$this->checked;

        return $checked ? $this->checked : $this->unchecked;
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
        $value = is_null($this->value) ? $this->default : $this->value;

        if(is_null($value)){
            return false;
        }

        return (string)$value === (string)$this->checked;
    }

    public function build(): Collection
    {
        $this->isChecked() ? $this->setAttribute('checked', 'checked') : $this->removeAttribute('checked');

        return parent::build();
    }

}