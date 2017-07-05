<?php

namespace RS\Form\View;

use RS\NView\Document;
use RS\NView\ViewController;

class Main extends ViewController {

	private function renderField(Document $view,$field,$fieldData,$fieldName,$useGap = false) {
		$field['errors'] = $fieldData['errors']->get($field['errorName']);
		$field = view($field['view'],$field);
		if($useGap) {
			$view->set("//*[@data-v.field='{$fieldName}']/child-gap()",$field);
		} else {
			$view->set("//*[@data-v.field='{$fieldName}']",$field);
		}
	}

	public function render(Document $view,array $data): Document {
		if(!isset($data['fields'][0])) {
			$data['fields'] = [$data['fields']];
		}
		foreach($data['fields'] as $fields ) {
			foreach ($fields as $fieldName => $fieldData) {
				if(isset($fieldData[0])) {
					foreach($fieldData as $iFieldData) {
						$this->renderField($view,$iFieldData,$data,$fieldName,true);
					}
				} else {
					$this->renderField($view,$fieldData,$data,$fieldName,false);
				}
			}
		}
		return $view;
	}


}