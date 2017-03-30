<?php
/**
 * Created by PhpStorm.
 * User: param
 * Date: 07/03/2017
 * Time: 11:00
 */

namespace App\View\Form\Fields;

use RS\NView\Document;


class Checkbox extends  Input {

	public function render(Document $view, array $data): Document {
		$this->renderAttributes($view,$data);
		$this->renderValue($view,$data);
		$this->renderName($view,$data);
		$this->renderError($view,$data);

		return $view;
	}


}