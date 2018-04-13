<?php

namespace RS\Form\Fields;


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
        if ($this->isChecked()) {
            return $this->checked;
        }
        return $this->unchecked;
    }

    public function getHTMLValue()
    {
        return $this->checked;
    }

    public function getUnCheckedValue(){
        return $this->unchecked;
    }

    public function getCheckedValue(){
        return $this->checked;
    }

    /*
     * Is this checkbox checked
     * @return bool
     */
    public function isChecked():bool{

        return !is_null($this->value) || !is_null($this->default);
    }

    public function setValue($value):AbstractField
    {
        parent::setValue($value);

        $this->isChecked() ? $this->setAttribute('checked','checked') : $this->removeAttribute('checked');

        return $this;
    }

}