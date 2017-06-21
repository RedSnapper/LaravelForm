<?php

namespace RS\Form\Fields;

class Checkbox extends AbstractField {

	protected $view = "form.fields.checkbox";

	protected $type = "checkable";


	public function __construct(string $name = null, $checked = 1, $unchecked = 0) {
		$this->name = $name;
		$this->value = $checked;
		$this->unchecked = $unchecked;
		$this->attributes = collect([]);
	}

}