<?php

namespace RS\Form\Fields;

class Checkbox extends AbstractField {

	protected $view = "form.fields.checkbox";

	protected $type = "checkable";

	public function __construct(string $name = '', $value = 1) {
		$this->name = $name;
		$this->value = $value;
		$this->attributes = collect([]);
	}

}