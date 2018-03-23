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

		$this->attributes = collect([]);
        $this->setName($name);
		$this->options = $this->setOptions($list);
	}

    public function data() : Collection {
        $data = parent::data();

        return $data->merge(['options'=>$this->options]);
    }

    public function getOptions(){
        return $this->options;
    }

	protected function setOptions($list): Collection {

		return collect($list)->map(function ($item, $key) {

			if (!is_array($item)) {
				return $this->option($key, $item);
			}

			if($this->isExplicitOption($item)){
                return $this->setExplicitOptions($item);
            }

			return $this->optgroup($key, $item);
		})->values();
	}

	protected function option($value, $display, bool $disabled = false): \stdClass {
		$option = new \stdClass();
		$option->label = $display;
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

	protected function setExplicitOptions($item){
        if(is_array($item['value'])){
            return $this->optgroup($item['label'], $item['value']);
        }

        return $this->option($item['value'], $item['label'] , @$item['disabled'] ?? false);
    }

    protected function isExplicitOption($item):bool{
	    return array_has($item,'label') && array_has($item,'value');
    }


}