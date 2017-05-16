<?php

namespace RS\Form\Fields;

class Checkbox extends AbstractField {

	protected $view = "form.fields.checkbox";

	protected $type = "checkable";

	public function __construct(string $name, $value = null) {
		$this->name = $name;
		$this->value = $value;
		$this->attributes = collect([]);
	}

	/**
	 * checked() can be overwritten to supply it's own
	 * interpretation of the value set to it.
	 * @return bool
	 */
	public function checked() : bool {
		return (bool) $this->value;
	}

}