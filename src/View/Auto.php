<?php

namespace RS\Form\View;

use RS\NView\ViewController;
use RS\NView\Document;

class Auto extends ViewController{

	private function doRender(array $data,array $fields,Document $view) {
		if(isset($fields[0]) && is_array($fields[0])) {
			foreach($fields as $field) {
				$this->doRender($data,$field,$view);
			}
		} else {
			$fields['errors'] = $data['errors']->get($field['errorName']);
			$fields['model'] = @$data['model'];
			$fields['master'] = @$data['master'];
			$fields['subscriber'] = @$data['subscriber'];
			$fieldView = view($fields['view'],$fields);
			$view->set("./child-gap()",$fieldView);
		}
	}

	public function render(Document $view,array $data): Document {

		foreach ($data['fields'] as $fieldData) {
			$this->doRender($data,$fieldData,$view);
		}

		return $view;
	}
}