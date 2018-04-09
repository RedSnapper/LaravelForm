<?php

namespace RS\Form;

use Illuminate\Support\Collection;
use RS\Form\Fields\AbstractField;

class FormletView
{
    /**
     * The formlet
     *
     * @var Formlet
     */
    protected $formlet;

    /**
     * The formlet fields
     *
     * @var Collection
     */
    protected $fields;

    /**
     * Child formlet collection
     *
     * @var FormletViewCollection
     */
    protected $children;

    public function __construct(Formlet $formlet)
    {
        $this->formlet = $formlet;
        $this->fields = $formlet->fields();
        $this->children = new FormletViewCollection($formlet->formlets());
    }

    /**
     * Get a named field for this formlet
     *
     * @param string $name
     * @return null|AbstractField
     */
    public function field(string $name): ?AbstractField
    {
        return $this->fields->get($name);
    }

    /**
     * Get all the fields for this formlet
     *
     * @return Collection
     */
    public function fields(): Collection
    {
        return $this->fields;
    }

    /**
     * Get a collection of formlets
     * @param null|string $name
     * @return mixed
     */
    public function get(string $name = null)
    {
        if (is_null($name)) {
            return $this->children;
        }
        return $this->children->get($name);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Get the first named formlet from
     * the collection
     *
     * @param string $name
     * @return mixed
     */
    public function first($name)
    {

        $names = collect(explode('.', $name));

        return $names->reduce(function ($carry, $item) {
            return $carry->get($item)->first();
        }, $this->children);
    }

}

