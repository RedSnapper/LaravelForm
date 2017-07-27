<?php

namespace RS\Form\View\Fields;

use RS\NView\Document;

class Textarea extends Field {

	protected $rootElement = "textarea";

	public function render(Document $view, array $data): Document {

		$this->renderDefaults($view,$data);
		$this->renderValue($view,$data);

		return $view;
	}

	protected function renderValue(Document $view, $data): void {

		$element = $this->rootElement;

		if(!is_null(@$data['value'])){
			$view->set("//h:$element/child-gap()",e($data['value']));
		}

	}

}