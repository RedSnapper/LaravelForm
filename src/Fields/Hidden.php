<?php

namespace RS\Form\Fields;

class Hidden extends AbstractField {

	protected $includeID = false;

    protected $view = "form::fields.hidden";

	public function __construct(string $name) {
        $this->attributes = collect(['type'=>'hidden','autocomplete'=>'off']);
	    $this->setName($name);
	}

}