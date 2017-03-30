<?php

namespace App\View\Form\Fields;

use RS\NView\Document;

class Radio extends Field {

	protected $radioTemplate;

	public function render(Document $view, array $data): Document {

		$this->renderName($view,$data);
		$this->renderAttributes($view,$data);
		$this->renderError($view,$data);
		$this->renderLabel($view,$data);

		$this->radioTemplate = $view->consume("//*[@data-v.xp='radio']");

		$this->renderOptions($view, $data);

		return $view;
	}

	public function renderOptions(Document $view, array $data) {

		$items = $data['options'];
		$selected = $data['value'];

		foreach ($items as $item) {
			$childView = $this->renderOption($item, $selected);
			$view->set("//*[@data-v.xp='radios']/child-gap()", $childView);
		}

	}


	protected function renderOption($item, $selected): Document {

		$element = $this->rootElement;
		$view = new Document($this->radioTemplate);

		$view->set("//h:$element/@value", $item->value);


		$view->set("//*[@data-v.xp='label']",$item->display);

		if($item->disabled) {
			$view->set("//h:$element/@disabled","disabled");
		}

		if ($this->isSelected($item->value, $selected)) {
			$view->set("//h:$element/@checked", "checked");
		}

		return $view;
	}

	/**
	 * Determine if the value is selected.
	 *
	 * @param  string $value
	 * @param  string $selected
	 * @return bool
	 */
	protected function isSelected($value, $selected): bool {
		return ((string)$value == (string)$selected);
	}

}