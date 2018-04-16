<?php

namespace RS\Form\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

trait HasRelationships
{

    protected $relationsMap = [
      HasOne::class => "hasOne",
      HasMany::class => "hasMany"
    ];

    /**
     * Add relation to form
     * @param string $relationKey
     * @param string $formlet
     * @param int    $count
     */
    public function relation(string $relationKey, string $formlet, int $count = 1)
    {

        if(!isset($this->model)){
            return;
        }

        $relation = $this->getRelationshipFromMethod($relationKey);

        if($method = @$this->relationsMap[get_class($relation)]){
            $this->$method($relation,$relationKey,$formlet,$count);
        }

    }

    /**
     * HasOne Relation
     * @param Relation $relation
     * @param string   $relationKey
     * @param string   $formlet
     * @param int      $count
     */
    protected function hasOne(Relation $relation,string $relationKey,string $formlet,int $count){

        if ($this->model->exists) {
            $formlet = $this->addFormlet($relationKey, $formlet);
            $formlet->model($relation->getResults());
        }else{
            $this->addFormlet($relationKey, $formlet);
        }
    }

    /**
     * HasMany relation
     * @param Relation $relation
     * @param string   $relationKey
     * @param string   $formlet
     * @param int      $count
     */
    protected function hasMany(Relation $relation,string $relationKey,string $formlet,int $count){

        if ($this->model->exists) {
            $relation->getResults()->each(function(Model $model) use($relationKey,$formlet){
                $formlet = $this->addFormlet($relationKey, $formlet);
                $formlet->model($model);
            });
        }else{
            $this->addFormlet($relationKey, $formlet,$count);
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