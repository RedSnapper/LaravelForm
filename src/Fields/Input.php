<?php

namespace RS\Form\Fields;

class Input extends AbstractField
{

    protected $view = "form::fields.input";

    /**
     * The types of inputs to not fill values on by default.
     *
     * @var array
     */
    protected $skipValueTypes = ['password', 'file'];

    public function __construct(string $type, string $name)
    {

        $this->attributes = collect(['type' => $type]);

        if (in_array($type, $this->skipValueTypes)) {
            $this->guarded = true;
        }

        if($type === "password"){
            //Pen test related vulnerable to xss attacks
            $this->setAttribute('autocomplete','off');
        }

        $this->setName($name);
    }

    /**
     * Multi select
     *
     * @param  boolean  $multiple
     * @return AbstractField
     */
    public function multiple($multiple = true): AbstractField
    {
        if ($this->getAttribute("type") == "file") {
            $multiple ? $this->setAttribute('multiple')
              : $this->removeAttribute("multiple");
        }
        return parent::multiple();
    }

}