<?php

namespace RS\Form\Fields;

class Input extends AbstractField {

	protected $view = "form::fields.input";

    /**
     * The types of inputs to not fill values on by default.
     *
     * @var array
     */
	protected  $skipValueTypes = ['password','file'];

	public function __construct(string $type, string $name) {

	    $this->attributes = collect(['type'=>$type]);

		if(in_array($type,$this->skipValueTypes)){
		    $this->guarded = true;
        }

        $this->setName($name);
	}




}