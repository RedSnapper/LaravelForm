<?php

namespace RS\Form\View\Fields;

use RS\NView\Document;

class Input extends Field {


	public function render(Document $view, array $data): Document {

		$this->renderDefaults($view,$data);
		$this->renderValue($view,$data);

		return $view;
	}



}