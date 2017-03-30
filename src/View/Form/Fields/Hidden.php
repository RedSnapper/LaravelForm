<?php

namespace App\View\Form\Fields;

use RS\NView\Document;

class Hidden extends Field {


	public function render(Document $view, array $data): Document {

		$this->renderAttributes($view,$data);
		$this->renderValue($view,$data);
		$this->renderName($view,$data);
		$this->renderValue($view,$data);

		return $view;
	}

}