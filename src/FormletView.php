<?php

namespace RS\Form;

use Illuminate\Support\Collection;

class FormletView
{
    /**
     * The formlet
     * @var Formlet
     */
    protected $formlet;

    /**
     * The formlet fields
     * @var Collection
     */
    protected $fields;

    /**
     * Child formlet collection
     * @var FormletViewCollection
     */
    protected $children;

    public function __construct(Formlet $formlet)
    {
        $this->formlet = $formlet;
        $this->fields = $formlet->fields();
        $this->children = new FormletViewCollection($formlet->formlets());
    }

    public function field($name)
    {
        return $this->fields->get($name);
    }

    public function fields(): Collection
    {
        return $this->fields;
    }

    public function get($name = null)
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

    public function first($name)
    {

        $names = collect(explode('.', $name));

        return $names->reduce(function ($carry, $item) {
            return $carry->get($item)->first();
        }, $this->children);

    }

}

