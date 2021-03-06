<?php

namespace RS\Form\Concerns;

use Illuminate\Support\Collection;
use RS\Form\Formlet;

/**
 * Trait ManagesPosts
 *
 * @mixin Formlet
 */
trait ManagesPosts
{

    /*
     * Have the form fields been bound
     * by the post
     * @var bool
     */
    public $bound = false;

    /**
     * Returns the posted values for this formlet
     * Only fields set in the view will appear here
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function postData(string $name = null)
    {

        $this->populate();

        if (is_null($name)) {
            return $this->activeValues();
        }

        if ($this->field($name) && $this->field($name)->isActive()) {
            return $this->field($name)->getValue();
        }

        return null;
    }

    /**
     * Returns all the posted values
     * Only fields set in the view will appear here
     *
     * @return Collection
     */
    public function allPostData(): Collection
    {

        $this->populate();

        return $this->formletPost();
    }

    /**
     * Returns subscription data for a group of formlets
     *
     * @param  string  $name
     * @return array
     */
    public function subscriptionData(string $name): array
    {

        $this->populate();

        $formlets = $this->formlets($name);

        if ($formlets->count() == 0) {
            return [];
        }

        $keyName = $formlets->first()->related->getKeyName();

        return $this->allPostData()->get($name)
          ->filter(function ($item) use ($keyName) {
              return $item->get($keyName) !== false;
          })
          ->keyBy($keyName)
          ->map->except($keyName)
          ->toArray();
    }

    /**
     * Method called when storing
     */
    public function persist()
    {

        if (isset($this->model)) {
            $this->model = $this->model->create($this->postData()->all());
        }
        return $this->model;
    }

    /**
     * Method called when updating
     */
    public function edit()
    {
        if (isset($this->model)) {
            $this->model->update($this->postData()->all());
        }
        return $this->model;
    }

    /**
     * Store method for the form request
     *
     * @return mixed
     */
    public function store()
    {

        $this->validate();

        $this->populate();

        return $this->persist();
    }

    /**
     * Update method for the form request
     *
     * @return mixed
     */
    public function update()
    {

        $this->validate();

        $this->populate();

        return $this->edit();
    }

    /**
     * Maps the formlets to the values stored in the fields
     *
     * @return Collection
     */
    protected function formletsPost(): Collection
    {

        return $this->formlets->map(function (Collection $forms) {
            return $forms->map(function (Formlet $formlet) {

                return $formlet->formletPost();
            });
        });
    }

    protected function formletPost()
    {
        $values = $this->activeValues();

        return $values->merge($this->formletsPost());
    }

    /**
     * @return mixed
     */
    protected function activeValues()
    {
        return $this->fields()->active()->mapToValues();
    }

}