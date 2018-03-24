<?php

namespace RS\Form\Fields;

class Input extends AbstractField {

	protected $view = "form::fields.input";

	public function __construct(string $type, string $name) {
		$this->attributes = collect(['type'=>$type]);
        $this->setName($name);
	}




}