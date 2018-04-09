<?php

namespace RS\Form;

use Illuminate\Support\Collection;

class FormletViewCollection implements \IteratorAggregate
{
    /**
     * @var Collection
     */
    public $formlets;

    public function __construct(Collection $formlets)
    {
        $this->formlets = $formlets->map(function (Collection $forms) {
            return $forms->map(function (Formlet $formlet) {
                return new FormletView($formlet);
            });
        });
    }

    /**
     * Renders all of the child formlets
     * @return string
     */
    public function renderAll(): string
    {
        return $this->renderFormlets($this)
            ->flatten()
            ->map->render()
            ->implode('');
    }

    /**
     * Get the first named formlet from
     * the collection
     *
     * @param string $name
     * @return mixed
     */
    public function first(string $name)
    {

        $names = collect(explode('.', $name));

        return $names->reduce(function ($carry, $item) {
            return $carry->get($item)->first();
        }, $this->formlets);

    }

    /**
     * Get a collection of formlets
     * @param null|string $name
     * @return mixed
     */
    public function get(string $name = null)
    {
        if (is_null($name)) {
            return $this->formlets;
        }
        return $this->formlets->get($name);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->formlets->toArray());
    }

    protected function renderFormlets(FormletViewCollection $collection): Collection
    {

        return collect($collection)->map(function ($forms) {
            return collect($forms)->map(function ($formlet) {
                $collection = $this->renderFormlets($formlet->get());
                $collection->prepend($formlet->fields()->map->render());
                return $collection;
            });
        });
    }

}

