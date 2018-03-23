<?php

namespace RS\Form\Fields;

class Checkbox extends AbstractField {

	protected $view = "form.fields.checkbox";
	protected $type = "checkable";

	public function __construct(string $name = null, $checked = true, $unchecked = false) {
        $this->attributes = collect([]);
	    $this->setName($name);
		$this->checked = $checked;
		$this->unchecked = $unchecked;
	}



}