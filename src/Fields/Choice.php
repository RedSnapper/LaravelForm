<?php
/**
 * Created by PhpStorm.
 * User: param
 * Date: 27/03/2017
 * Time: 16:02
 */

namespace RS\Form\Fields;

use Illuminate\Support\Collection;

class Choice extends AbstractField
{
    /**
     * @var Collection
     */
    protected $options;

    /**
     * @var Collection
     */
    protected $optionList;

    /**
     * String used for selected values options/radios
     *
     * @var string
     */
    protected $selectedOption = "selected";

    public function __construct(string $name, $list = [])
    {

        $this->attributes = collect([]);
        $this->setName($name);
        $this->optionList = collect($list);
    }

    /**
     * Set placeholder.
     *
     * @param string $string
     * @return AbstractField
     */
    public function placeholder(string $string): AbstractField
    {

        $this->optionList->prepend($string, '');

        return $this;
    }

    public function build(): Collection
    {

        $data = parent::build();

        $this->options = $this->setOptions($this->optionList);

        return $data->merge(['options' => $this->getOptionsWithValues()]);
    }

    public function getOptions()
    {
        return $this->options;
    }

    protected function getDefaultOptionAttributes(): array
    {
        return [];
    }

    protected function setOptions($list): Collection
    {

        return collect($list)->map(function ($item, $key) {

            if (!is_array($item)) {
                return $this->option($key, $item);
            }

            if ($this->isExplicitOption($item)) {
                return $this->setExplicitOptions($item);
            }

            return $this->optionGroup($key, $item);
        })->values();
    }

    protected function option($value, $display, $attributes = [], $extras = []): \stdClass
    {
        $option = new \stdClass();
        $option->label = $display;
        $option->value = $value;
        $option->attributes = $this->getOptionAttributes($attributes);
        $option->extras = $extras;
        return $option;
    }

    protected function optionGroup($label, $options = []): \stdClass
    {

        $group = new \stdClass();
        $group->label = $label;
        $group->options = $this->setOptions($options);

        return $group;
    }

    protected function setExplicitOptions($item)
    {
        if (is_array($item['value'])) {
            return $this->optionGroup($item['label'], $item['value']);
        }

        return $this->option(
          $item['value'],
          $item['label'],
          $this->optionAttributes(@$item['attributes'] ?? []),
          @$item['extras']
        );
    }

    protected function isExplicitOption($item): bool
    {
        return array_has($item, 'label') && array_has($item, 'value');
    }

    private function optionAttributes(array $attributes)
    {
        return collect($attributes)->mapWithKeys(function ($attribute) {
            if (!is_array($attribute)) {
                return [$attribute => $attribute];
            }
            return $attribute;
        });
    }

    /**
     * Add selected to any options that match the values set
     */
    public function getOptionsWithValues(): Collection
    {

        if (!is_null($this->getValue())) {
            return $this->getOptions()->map(\Closure::fromCallable([$this, 'mapSelectedOptions']));
        }

        return $this->getOptions();
    }

    protected function mapSelectedOptions($option)
    {

        if (isset($option->options)) {
            $option->options = $option->options->map(\Closure::fromCallable([$this, 'mapSelectedOptions']));
        } else {
            if ($this->isSelected($option->value)) {
                $option->attributes->put($this->selectedOption, $this->selectedOption);
            }
        }
        return $option;
    }

    /**
     * Determine if the value is selected.
     *
     * @param string $option
     * @return bool
     */
    protected function isSelected($option): bool
    {

        $value = $this->getValue();

        if (is_array($value)) {
            return in_array($option, $value);
        } elseif ($value instanceof Collection) {
            return $value->contains('id', $option);
        }
        return ((string)$option == (string)$value);
    }

    protected function getOptionAttributes($attributes): Collection
    {
        return collect($this->getDefaultOptionAttributes())->merge($attributes);
    }

}