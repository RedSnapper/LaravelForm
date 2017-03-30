<?php

namespace App\View\Form;

use RS\NView\Document;
use RS\NView\ViewController;

class Main extends ViewController {
	public function render(Document $view,array $data): Document {

		foreach ($data['fields'] as $fieldData) {
			$name = $fieldData['name'];;
			$fieldData['errors'] = $data['errors']->get($name);
			$field = view($fieldData['view'],$fieldData);

			$view->set("//*[@data-v.field='{$fieldData['field']}']",$field);
		}

		return $view;
	}


}