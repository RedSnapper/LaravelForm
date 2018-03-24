<?php

namespace RS\Form\Fields;

class Hidden extends AbstractField {

	protected $view = "form::fields.hidden";

	public function __construct(string $name) {
        $this->attributes = collect(['type'=>'hidden']);
	    $this->setName($name);
	}

}