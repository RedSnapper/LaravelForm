<?php
/**
 * Created by PhpStorm.
 * User: param
 * Date: 27/03/2017
 * Time: 16:02
 */

namespace RS\Form\Fields;

use Illuminate\Support\Collection;

class Choice extends AbstractField {
	/**
	 * @var Collection
	 */
	protected $options;

	public function __construct(string $name, $list = []) {
		$this->name = $name;
		$this->attributes = collect([]);
		$this->options = $this->setOptions($list);
	}

	protected function setOptions($list): Collection {

		return collect($list)->map(function ($item, $key) {

			if (!is_array($item)) {
				return $this->option($key, $item);
			}
			return $this->optgroup($key, $item);
		})->values();
	}

	protected function option($value, $display, bool $disabled = false): \stdClass {
		$option = new \stdClass();
		$option->display = $display;
		$option->value = $value;
		$option->disabled = $disabled;
		return $option;
	}

	protected function optgroup($label, $options = []): \stdClass {

		$group = new \stdClass();
		$group->label = $label;

		$group->options = $this->setOptions($options);

		return $group;
	}

	public function getData() : array {
		$data = parent::getData();

		$data['options'] = $this->options;

		return $data;
	}
}