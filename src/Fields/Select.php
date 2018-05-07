<?php

namespace RS\Form\Fields;


class Select extends Choice {

	protected $view = "form::fields.select";

	/**
	 * Multi select
	 *
	 * @param boolean $multiple
	 * @return AbstractField
	 */
	public function multiple($multiple = true): AbstractField {

		$multiple ? $this->setAttribute('multiple')
		  : $this->removeAttribute("multiple");

		return parent::multiple();
	}

}