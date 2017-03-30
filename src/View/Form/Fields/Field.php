<?php
/**
 * Created by PhpStorm.
 * User: param
 * Date: 27/03/2017
 * Time: 15:41
 */

namespace App\View\Form\Fields;

use RS\NView\ViewController;
use RS\NView\Document;

class Field extends ViewController {

	protected $rootElement = "input";

	protected function renderDefaults(Document $view, $data){
		$this->renderName($view,$data);
		$this->renderAttributes($view,$data);
		$this->renderId($view,$data);
		$this->renderError($view,$data);
		$this->renderLabel($view,$data);
	}

	protected function renderLabel(Document $view,array $data) {
		if (!isset($data['label'])){
			$view->set("//*[@data-v.xp='label']");
		}else{
			$view->set("//*[@data-v.xp='label']/@for",$data['name']);
		}
	}

	protected function renderAttributes(Document $view, $data) {

		$element = $this->rootElement;

		if(isset($data['attributes'])){
			foreach ($data['attributes'] as $attribute => $value){
				$view->set("//h:$element/@$attribute",$value);
			}
		}
	}

	protected function renderValue(Document $view, $data) {

		$element = $this->rootElement;

		if(!is_null(@$data['value'])){
			$view->set("//h:$element/@value",e($data['value']));
		}
	}

	protected function renderName(Document $view, $data) {

		$element = $this->rootElement;

		$view->set("//h:$element/@name",$data['name']);
	}

	protected function renderId(Document $view, $data){

		$element = $this->rootElement;

		$view->set("//h:$element/@id",$data['name']);
	}

	protected function renderError(Document $view, $data) {
		$errors = $data['errors'];
		if(count($errors)){
			$view->set("/*/@class/child-gap()"," has-error");
		}
	}



}