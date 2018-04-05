<?php

namespace RS\Form\Concerns;

use Illuminate\Support\Collection;
use RS\Form\Fields\AbstractField;
use RS\Form\Formlet;

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
     * @return Collection
     */
    public function postData():Collection{

        $this->preparePost();

        return $this->fields()->map->getValue();
    }

    /**
     * Returns all the posted values
     * Only fields set in the view will appear here
     * @return Collection
     */
    public function allPostData():Collection{

        $this->preparePost();

        return $this->formlets->map(function(Collection $forms){
            return $forms->map(function(Formlet $formlet){
                return $formlet->fields()->map->getValue();
            });
        });
    }

    /**
     * Method called when storing
     */
    public function persist(){

        if (isset($this->model)) {
            $this->model = $this->model->create($this->postData());
        }
        return $this->model;
    }

    /**
     * Method called when updating
     */
    public function edit(){
        if (isset($this->model)) {
            $this->model = $this->model->fill($this->postData());
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

        $this->prepareFormlets();

        $this->iterateFields(function(AbstractField $field,Formlet $formlet){
            if ($request = $this->request($field->getInstanceName())) {
                $field->setValue($request);
            }
            $formlet->bound = true;
        });

    }

}