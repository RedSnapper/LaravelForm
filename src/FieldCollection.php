<?php

namespace RS\Form;

use Illuminate\Support\Collection;
use RS\Form\Fields\AbstractField;

class FieldCollection extends Collection
{
    /**
     * Filter only fields which are not disabled
     *
     * @return $this
     */
    public function active(): self
    {
        return $this->filter(function (AbstractField $field) {
            return $field->isActive();
        });
    }

    /**
     * Get the value of each field
     *
     * @return $this
     */
    public function mapToValues(): self
    {
        return $this->map(function (AbstractField $field) {
            return $field->getValue();
        });
    }

    /**
     * Add a field
     *
     * @param  AbstractField  $field
     * @return $this
     */
    public function addField(AbstractField $field): self
    {
        return $this->put($field->getName(), $field);
    }

    /**
     * Retrieve fields by name
     *
     * @param  array  $names
     * @return FieldCollection
     */
    public function byName(array $names)
    {
        return $this->filter(function ($value, $key) use ($names) {
            return in_array($key, $names);
        });
    }

}

