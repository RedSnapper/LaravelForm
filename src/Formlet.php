<?php
declare(strict_types=1);

namespace RS\Form;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RS\Form\Concerns\{
    ManagesForm, ManagesPosts, ValidatesForm
};
use RS\Form\Fields\AbstractField;

abstract class Formlet
{
    use ManagesForm, ValidatesForm, ManagesPosts;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * Session storage.
     *
     * @var Session
     */
    protected $session;

    /**
     * The request
     *
     * @var Request
     */
    public $request;

    /**
     * The formlet name
     *
     * @var string
     */
    public $name = "main";

    /**
     * Fields added to the form
     *
     * @var Collection
     */
    protected $fields;

    /**
     * The current model instance for the form.
     *
     * @var mixed
     */
    protected $model;

    /**
     * All the formlets attached to this form.
     *
     * @var Collection
     */
    protected $formlets;

    /**
     * Formlet key.
     *
     * @var int
     */
    protected $key = 0;

    abstract public function prepare(): void;

    public function initialize()
    {

        $this->attributes = collect($this->attributes);
        $this->attributes->put('action', $this->url->current());
        $this->fields = collect();
        $this->formlets = collect();
        $this->errors = $this->session->get('errors') ?? collect();

        $this->prepare();

    }

    /**
     * Set request on form.
     *
     * @param Request $request
     * @return Formlet
     */
    public function setRequest(Request $request): Formlet
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the session store for formlets
     *
     * @param Session $session
     * @return Formlet
     */
    public function setSessionStore(Session $session): Formlet
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Set url generator
     *
     * @param UrlGenerator $url
     * @return Formlet
     */
    public function setUrlGenerator(UrlGenerator $url): Formlet
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Set the name for the formlet
     *
     * @param string $name
     * @return Formlet
     */
    public function name(string $name):Formlet{
        $this->name = $name;
        return $this;
    }

    /**
     * Set the name for the formlet
     *
     * @param int $key
     * @return Formlet
     */
    public function key(int $key):Formlet{
        $this->key = $key;
        return $this;
    }

    /**
     * Get the key for the formlet
     *
     * @return int $key
     */
    public function getKey():int{
        return $this->key;
    }

    /**
     * Build the current form
     *
     * @return Collection
     */
    public function build(): Collection
    {

        $this->populate();

        return collect([
            'form' => collect([
                'hidden' => $this->getHiddenFields(),
                'attributes' => $this->attributes->sortKeys()
            ]),
            'formlets' => $this->formlets->map(function(Collection $forms){
                return $forms->map(function(Formlet $formlet){
                    return [
                      'fields'=>$formlet->fields()
                    ];
                });
            })
        ]);
    }

    /**
     * @param AbstractField $field
     * @return Formlet
     */
    public function add(AbstractField $field): Formlet
    {
        $this->fields->put($field->getName(), $field);
        return $this;
    }

    /**
     * Set the model instance on the form builder.
     *
     * @param  mixed $model
     * @return Formlet
     */
    public function model($model): Formlet
    {
        $this->model = $model;
        return $this;
    }

    public function fields($name = null): Collection
    {
        $names = is_array($name) ? $name : func_get_args();

        if (count($names) == 0) {
            return $this->fields;
        }

        return $this->fields->filter(function ($value, $key) use ($names) {
            return in_array($key, $names);
        });
    }

    /**
     * Add a formlet to this form
     *
     * @param $relation
     * @param $formlet
     */
    public function addFormlet($relation,$formlet){

        $formlet = $formlet instanceof Formlet ? $formlet : app()->make($formlet);

        $formlet->name($relation);

        if(!$this->formlets->has($relation)){
            $this->formlets->put($relation,collect());
        }

        $formlet->key($this->formlets->get($relation)->count());

        $this->formlets[$relation][] = $formlet;

    }

    /**
     * Returns the formlets added to this form
     * @param string|null $name
     * @return Collection
     */
    public function formlets(string $name = null):Collection{

        if (is_null($name)) {
            return $this->formlets;
        }

        return $this->formlets->get($name);
    }

    /**
     * Populates all the fields
     * Populates the field from the request
     */
    protected function populate(): void
    {

        $this->prepareFormlets();

        $this->formlets->each(function(Collection $formlets){
            $formlets->each(function(Formlet $formlet){
                $formlet->fields()->each(function(AbstractField $field,$key) use ($formlet){
                    if ($value = $this->getValueAttribute($field->getInstanceName(),$key)) {
                        $field->setValue($value);
                    }

                    $this->populateErrors($field, $this->transformKey($field->getInstanceName()));
                });
            });
        });

    }

    protected function prepareFormlets(){

        $this->addFormlet($this->name,$this);

        $this->formlets->each(function(Collection $formlets){
            $formlets->each(function(Formlet $formlet){
                $formlet->fields()->each(function(AbstractField $field,$key) use ($formlet){

                    $instance = $formlet->name . "[" . $formlet->getKey() . "][" . $field->getName() . "]";
                    $field->setInstanceName($instance);
                });
            });
        });
    }

    /**
     * Get value from current Request
     *
     * @param $name
     * @return array|null|string
     */
    protected function request($name)
    {
        return $this->request->input($this->transformKey($name));
    }

    /**
     * Transform key from array to dot syntax.
     *
     * @param  string $key
     * @return mixed
     */
    protected function transformKey($key)
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
    }

    /**
     * Get the value that should be assigned to the field.
     *
     * @param  string $name
     * @param  string $modelKey
     * @return mixed
     */
    protected function getValueAttribute(string $name,string $modelKey)
    {

        $old = $this->old($name);
        if (!is_null($old)) {
            return $old;
        }

        if ($request = $this->request($name)) {
            return $request;
        }

        if (isset($this->model)) {
            return $this->getModelValueAttribute($modelKey);
        }

        return null;
    }

    /**
     * Get a value from the session's old input.
     *
     * @param  string $name
     * @return mixed
     */
    protected function old($name)
    {
        return $this->session->getOldInput($this->transformKey($name));
    }

    /**
     * Get the model value that should be assigned to the field.
     *
     * @param  string $name
     * @return mixed
     */
    protected function getModelValueAttribute($name)
    {
        return data_get($this->model, $this->transformKey($name));
    }

}