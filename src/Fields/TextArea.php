<?php

namespace RS\Form\Fields;

class TextArea extends AbstractField {

	protected $view = "form.fields.textarea";

	public function __construct(string $name) {
        $this->attributes = collect([]);
	    $this->setName($name);
	}


	/**
	 * Set Number of rows
	 *
	 * @param int $rows
	 * @return AbstractField
	 */
	public function rows(int $rows): AbstractField {
		$this->setAttribute('rows',$rows);
		return $this;
	}

	/**
	 * Set Number of cols
	 *
	 * @param int $cols
	 * @return AbstractField
	 */
	public function cols(int $cols): AbstractField {
		$this->setAttribute('cols',$cols);
		return $this;
	}

}