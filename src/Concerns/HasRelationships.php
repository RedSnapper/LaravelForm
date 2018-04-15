<?php

namespace RS\Form\Concerns;


use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

trait HasRelationships
{
    public function relation(string $relationKey, string $formlet)
    {

        if(!isset($this->model)){
            return;
        }

        $relation = $this->getRelationshipFromMethod($relationKey);


        $formlet = $this->addFormlet($relationKey, $formlet);

        if ($this->model->exists) {
            $formlet->model($relation->getResults());
        }
    }

    /**
     * Get relation from model
     *
     * @param $method
     * @return Relation
     * @throws LogicException
     */
    protected function getRelationshipFromMethod($method):Relation{

        if (!method_exists($this->model, $method)) {

            throw new LogicException(sprintf(
                '%s::%s method does not exist on the model', static::class, $method
            ));
        }

        $relation = $this->model->$method();

        if (!$relation instanceof Relation) {

            throw new LogicException(sprintf(
                '%s::%s must return a relationship instance', static::class, $method
            ));
        }

        return $relation;
    }
}