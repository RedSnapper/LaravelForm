<?php

namespace RS\Form\Fields;


class Select extends Choice {

	protected $view = "form.fields.select";

	/**
	 * Set placeholder. This option needs to be disabled.
	 *
	 * @param string $string
	 * @return AbstractField
	 */
	public function setPlaceholder(string $string): AbstractField {

		$this->options->prepend($this->option(null,$string,true));

		return $this;
	}

	/**
	 * Multi select
	 *
	 * @param boolean $multiple
	 * @return AbstractField
	 */
	public function setMultiple($multiple = true): AbstractField {

		$multiple ? $this->setAttribute('multiple')
		  : $this->removeAttribute("multiple");

		return $this;
	}

}