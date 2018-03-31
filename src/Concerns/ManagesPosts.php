<?php

namespace RS\Form\Concerns;



use Illuminate\Support\Collection;
use RS\Form\Fields\AbstractField;

trait ManagesPosts
{

    /*
     * Have the form fields been bound
     * by the post
     * @var bool
     */
    protected $bound = false;

    /**
     * Returns the posted values
     * Only fields set in the view will appear here
     * @return Collection
     */
    public function post():Collection{

        $this->preparePost();

        return $this->fields->map->getValue();
    }

    /**
     * Method called when storing
     */
    public function persist(){
        if (isset($this->model)) {
            $this->model = $this->model->create($this->post());
        }
        return $this->model;
    }

    /**
     * Method called when updating
     */
    public function edit(){
        if (isset($this->model)) {
            $this->model = $this->model->fill($this->post());
            $this->model->save();
        }
        return $this->model;
    }

    /**
     * Store method for the form request
     */
    public function store(){

        $this->validate();

        $this->preparePost();

        return $this->persist();
    }

    /**
     * Update method for the form request
     */
    public function update(){

        $this->validate();

        $this->preparePost();

        return $this->edit();
    }

    /**
     * Populate the fields from the post
     *
     */
    protected function preparePost(): void
    {

        if($this->bound){
            return;
        }

        $this->fields->each(function (AbstractField $field, $key) {
            if ($request = $this->request($key)) {
                $field->setValue($request);
            }
        });

        $this->bound = true;
    }

}