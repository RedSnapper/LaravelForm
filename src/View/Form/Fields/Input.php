<?php

namespace App\View\Form\Fields;

use RS\NView\Document;
use RS\NView\ViewController;

class Input extends Field {


	public function render(Document $view, array $data): Document {

		$this->renderDefaults($view,$data);
		$this->renderValue($view,$data);

		return $view;
	}



}