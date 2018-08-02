<?php

namespace RS\Form\Fields;

class HoneyPot extends AbstractField {

	protected $includeID = false;

    protected $view = "form::fields.honeypot";

	public function __construct(string $name) {
        $this->attributes = collect(['type'=>'text','autocomplete'=>'off']);
	    $this->setName($name);
	}

}