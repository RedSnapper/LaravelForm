<?php
declare(strict_types=1);

namespace RS\Form;

use Illuminate\Support\Collection;

class FormletView
{
    /**
     * @var Collection
     */
    public $fields;

    /**
     * @var Collection
     */
    public $formlets;

    public function __construct(Collection $fields,FormletViewCollection $formlets)
    {
        $this->fields = $fields;
        $this->formlets = $formlets;
    }

    public function field($name){
        return $this->fields->get($name);
    }

    public function fields(){
        return $this->fields;
    }

    public function get($name=null)
    {
        if(is_null($name)){
            return $this->formlets;
        }
        return $this->formlets->get($name);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function first($name){

        $names = collect(explode('.',$name));

        return $names->reduce(function($carry,$item){
            return $carry->get($item)->first();
        },$this->formlets);

    }

}

