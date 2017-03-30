<?php

namespace App\View\Form;

use RS\NView\ViewController;
use RS\NView\Document;

class Auto extends ViewController{

	public function render(Document $view,array $data): Document {

		foreach ($data['fields'] as $fieldData) {
			$name = $fieldData['name'];

			$fieldData['errors'] = $data['errors']->get($name);
			$field = view($fieldData['view'],$fieldData);

			$view->set("./child-gap()",$field);
		}

		return $view;
	}
}