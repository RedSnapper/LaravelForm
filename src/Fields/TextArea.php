<?php

namespace RS\Form\Fields;

class Textarea extends AbstractField {

	protected $view = "form.fields.textarea";

	public function __construct(string $name) {
		$this->name = $name;
		$this->attributes = collect([]);
	}


	/**
	 * Set Number of rows
	 *
	 * @param int $rows
	 * @return AbstractField
	 */
	public function setRows(int $rows): AbstractField {
		$this->setAttribute('rows',$rows);
		return $this;
	}

	/**
	 * Set Number of cols
	 *
	 * @param int $cols
	 * @return AbstractField
	 */
	public function setCols(int $cols): AbstractField {
		$this->setAttribute('cols',$cols);
		return $this;
	}

}