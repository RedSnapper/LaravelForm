<?php
/**
 * Created by PhpStorm.
 * User: param
 * Date: 27/03/2017
 * Time: 16:23
 */

namespace App\View\Form\Fields;

use PhpParser\Comment\Doc;
use RS\NView\Document;

class Select extends Field {

	protected $rootElement = "select";

	protected $optionTemplate;

	protected $optGroupTemplate;

	public function render(Document $view, array $data): Document {

		$this->optionTemplate = $view->consume("//h:option");
		$this->optGroupTemplate = $view->consume("//h:optgroup");

		$this->renderDefaults($view, $data);

		$this->renderOptions($view, $data);

		return $view;
	}

	public function renderOptions(Document $view, array $data) {

		$items = $data['options'];
		$selected = $data['value'];

		foreach ($items as $item) {
			if (isset($item->options)) {
				$childView = $this->renderOptGroup($item, $selected);
			} else {
				$childView = $this->renderOption($item, $selected);
			}

			$view->set("//h:select/child-gap()", $childView);
		}


	}

	protected function renderOptGroup($item, $selected): Document {
		$view = new Document($this->optGroupTemplate);
		$view->set("//h:optgroup/@label", $item->label);

		foreach ($item->options as $option) {

			$childView = $this->renderOption($option, $selected);

			$view->set("//h:optgroup/child-gap()", $childView);
		}

		return $view;
	}

	protected function renderOption($item, $selected): Document {
		$view = new Document($this->optionTemplate);
		$view->set("//h:option/@value", $item->value);
		$view->set("//h:option/child-gap()", $item->display);
		if($item->disabled) {
			$view->set("//h:option/@disabled","disabled");
		}

		if ($this->isSelected($item->value, $selected)) {
			$view->set("//h:option/@selected", "selected");
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
		if (is_array($selected)) {
			return in_array($value, $selected);
		}
		return ((string)$value == (string)$selected);
	}

}