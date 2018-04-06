<?php
declare(strict_types=1);

namespace RS\Form;

use Illuminate\Support\Collection;
use Traversable;

class FormletViewCollection implements \IteratorAggregate
{
    /**
     * @var Collection
     */
    public $formlets;

    public function __construct(Collection $formlets)
    {
        $this->formlets = $formlets->map(function(Collection $forms){
            return $forms->map(function(Formlet $formlet){
                return new FormletView($formlet->fields(),new FormletViewCollection($formlet->formlets()));
            });
        });
    }

    public function renderAll(){
        dd($this->renderFormlets($this)->flatten()->map->render());
    }

    protected function renderFormlets(FormletViewCollection $collection){

        return collect($collection)->map(function($forms){
            return collect($forms)->map(function($formlet){
                dd($formlet->get());
                return $formlet->fields()->map->render();
            });
        });
    }



    public function first($name){

        $names = collect(explode('.',$name));

        return $names->reduce(function($carry,$item){
            return $carry->get($item)->first();
        },$this->formlets);

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

    public function getIterator()
    {
        return new \ArrayIterator($this->formlets->toArray());
    }


}

