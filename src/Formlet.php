<?php

namespace RS\Form;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use RS\Form\Concerns\{HasRelationships, ManagesForm, ManagesPosts, ValidatesForm};
use RS\Form\Fields\AbstractField;

abstract class Formlet
{
    use ManagesForm,
        ValidatesForm,
        ManagesPosts,
        HasRelationships;

    /**
     * The formlet prefix
     * Use this to prefix form fields
     *
     * @var string|null
     */
    public $prefix;
    /**
     * The current model instance for the form.
     *
     * @var mixed
     */
    public $model;
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
    protected $request;
    /**
     * The input for this form
     *
     * @var FormletInput
     */
    protected $input;
    /**
     * The formlet name
     *
     * @var string
     */
    protected $name;
    /**
     * The formlet instance name
     *
     * @var string
     */
    protected $instanceName;
    /**
     * Fields added to the form
     *
     * @var FieldCollection
     */
    protected $fields;
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

    /**
     * Whether formlets have been prepared
     *
     * @var bool
     */
    protected $prepared = false;

    public function initialize()
    {

        $this->attributes = collect($this->attributes);
        $this->attributes->put('action', $this->url->current());
        $this->fields = new FieldCollection();
        $this->formlets = collect();
        $this->allErrors = new MessageBag();
        $this->errors = new MessageBag();
        $this->input = new FormletInput();
    }

    /**
     * Set request on form.
     *
     * @param  Request  $request
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
     * @param  Session  $session
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
     * @param  UrlGenerator  $url
     * @return Formlet
     */
    public function setUrlGenerator(UrlGenerator $url): Formlet
    {
        $this->url = $url;
        return $this;
    }

    public function setPrefix(?string $value = null): self
    {
        $this->prefix = $value;
        return $this;
    }

    /**
     * Set the name for the formlet
     *
     * @param  string  $name
     * @return Formlet
     */
    public function name(string $name): Formlet
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the name for the formlet
     *
     * @param  int  $key
     * @return Formlet
     */
    public function key(int $key): Formlet
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Build the current form
     *
     * @return Collection
     */
    public function build(): Collection
    {

        $this->populate(false);

        return collect([
            'form' => collect([
                'hidden' => $this->getHiddenFields(),
                'attributes' => $this->attributes->sortKeys()
            ]),
            'formlet' => $this
        ]);
    }

    /**
     * Populates all the fields
     * Populates the field from the request
     *
     * @param  bool  $isPost  Is the populate from a post
     */
    protected function populate(bool $isPost = true): void
    {
        if ($this->prepared) {
            return;
        }

        $this->setFieldNames();

        $this->setErrors();

        $this->setInputs($isPost);

        $this->populateFields();

        $this->populateErrors();
    }

    protected function setErrors(): void
    {
        $this->allErrors = optional($this->session->get('errors'))->getBag($this->getErrorBagName()) ?? new MessageBag();

        $this->iterateFormlets(function (Formlet $formlet) {
            $formlet->setErrors();
        });
    }

    /**
     * Set the input for all the formlets
     *
     * @param  bool  $isPost
     */
    protected function setInputs(bool $isPost)
    {
        $this->setInput($isPost);

        $this->iterateFormlets(function (Formlet $formlet) use ($isPost) {
            $formlet->setInputs($isPost);
        });
    }

    /**
     * Set the input for this formlet from the request
     * @param  bool  $isPost
     */
    protected function setInput(bool $isPost)
    {
        $this->input->add($this->mapRequest());
        if ($isPost) {
            $this->prepareForValidation();
        }
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        //
    }

    /**
     * Set all field names for this formlet
     *
     * @param  string  $prefix
     */
    protected function setFieldNames(string $prefix = ""): void
    {

        $this->prepare();
        $this->prepared = true;

        $prefix = $this->setFormletInstance($prefix);

        $this->fields()->each(function (AbstractField $field) {
            $this->setFieldName($field);
        });

        $this->iterateFormlets(function (Formlet $formlet) use ($prefix) {
            $formlet->setFieldNames($prefix);
        });
    }

    abstract public function prepare(): void;

    /**
     * Set the formlet instance based on parent formlets
     *
     * @param  string  $prefix
     * @return string
     */
    private function setFormletInstance(string $prefix): string
    {
        if (is_null($this->name)) {
            return "";
        }

        if ($prefix == "") {
            $formletInstance = $this->name."[".$this->getKey()."]";
        } else {
            $formletInstance = $prefix."[".$this->name."][".$this->getKey()."]";
        }

        $this->instanceName($formletInstance);

        return $formletInstance;
    }

    /**
     * Get the key for the formlet
     *
     * @return int $key
     */
    protected function getKey(): int
    {
        return $this->key;
    }

    /**
     * Set the instance name for the formlet
     *
     * @param  string  $name
     * @return Formlet
     */
    public function instanceName(string $name): Formlet
    {
        $this->instanceName = $name;
        return $this;
    }

    /**
     * Return fields for this formlet
     *
     * @param  null|string|array  $name
     * @return FieldCollection
     */
    public function fields($name = null)
    {
        $names = is_array($name) ? $name : func_get_args();

        if (count($names) == 0) {
            return $this->fields;
        }

        return $this->fields->byName($names);
    }

    /**
     * Set field instance name
     *
     * @param  AbstractField  $field
     */
    protected function setFieldName(AbstractField $field)
    {
        $name = $field->getName();

        if (!is_null($this->instanceName)) {
            $name = $this->instanceName."[".$name."]";
        }

        if (!is_null($this->prefix)) {
            $name = $this->prefix.":".$name;
        }

        $field->setInstanceName($name);
    }

    /**
     * Iterate child formlets
     *
     * @param  \Closure  $closure
     */
    protected function iterateFormlets(\Closure $closure)
    {
        $this->formlets->each(function (Collection $forms) use ($closure) {
            $forms->each(function (Formlet $formlet) use ($closure) {
                $closure($formlet);
            });
        });
    }

    /**
     * Populate Fields for this formlet
     */
    protected function populateFields(): void
    {

        $this->fields()->each(function (AbstractField $field) {
            $this->populateField($field);
        });

        $this->prepared = true;

        $this->iterateFormlets(function (Formlet $formlet) {
            $formlet->populateFields();
        });
    }

    /**
     * Populate formlet field
     *
     * @param  AbstractField  $field
     */
    protected function populateField(AbstractField $field): void
    {

        $value = $this->getValueAttribute($field);

        if (!is_null($value)) {
            $field->setValue($value);
        }

        $this->populateFieldErrors($field);
    }

    /**
     * Get the value that should be assigned to the field.
     * 1) Session Flash Data (Old Input)
     * 2) The request
     * 3) Model Attribute Data
     *
     * @param  string  $name
     * @param  string  $modelKey
     * @return mixed
     */
    protected function getValueAttribute(AbstractField $field)
    {
        $name = $field->getInstanceName();
        $modelKey = $field->getName();

        $old = $this->old($name);
        if (!is_null($old)) {
            return $old;
        }

        $request = $this->input->get($this->transformKey($modelKey));

        if (!is_null($request)) {
            return $request;
        }

        if ($this->model && $this->request->isMethod('GET') && !$field->isDirty()) {
            return $this->getModelValueAttribute($this->model, $modelKey);
        }

        return null;
    }

    /**
     * Get a value from the session's old input.
     *
     * @param  string  $name
     * @return mixed
     */
    protected function old($name)
    {
        return $this->session->getOldInput($this->transformKey($name));
    }

    /**
     * Transform key from array to dot syntax.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function transformKey($key)
    {
        if (is_null($key)) {
            return $key;
        } else {
            return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
        }
    }

    /**
     * Get value from current Request
     *
     * @param $name
     * @return array|null|string
     */
    protected function request($name)
    {
        $key = is_null($name) ? null : $this->transformKey($name);

        return $this->request->input($key) ?? $this->request->file($key);
    }

    /**
     * Merge new input into the current formlet's input array.
     *
     * @param  array  $input
     * @return $this
     */
    protected function mergeInput(array $input): self
    {
        $this->input->add($input);
        return $this;
    }

    /**
     * Get the model value that should be assigned to the field.
     *
     * @param  string  $name
     * @return mixed
     */
    protected function getModelValueAttribute($model, $name)
    {

        // If the model has pivot columns we should check them first
        // Before checking the model attributes
        // This is important as the we many have the same attribute
        // on the pivot and the related model. If the name is the same
        // as the related key name then we should get the model rather
        // than the pivot as this is the subscription value

        $data = null;

        if ($this->hasPivotColumns() && $this->related->getKeyName() !== $name) {
            $data = data_get($model, $this->getPivotAccessor().".".$this->transformKey($name));
        }

        if (!is_null($data)) {
            return $data;
        }

        return data_get($model, $this->transformKey($name));
    }

    /**
     * @param  AbstractField  $field
     * @return Formlet
     */
    public function add(AbstractField $field): Formlet
    {
        $this->fields->addField($field);
        return $this;
    }

    /**
     * Set the model instance on the form builder.
     *
     * @param  mixed  $model
     * @return Formlet
     */
    public function model($model): Formlet
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Does this model exist.
     *
     * @return bool
     */
    public function modelExists(): bool
    {
        return !is_null($this->model) && $this->model->exists;
    }

    /**
     * Return named field
     *
     * @param  string  $name
     * @return null|AbstractField
     */
    public function field(string $name): ?AbstractField
    {
        return $this->fields->get($name);
    }

    /**
     * Add a formlet(s) to form
     *
     * @param  string  $relation
     * @param  string  $class
     * @param  int  $count
     * @return Formlet
     */
    public function addFormlet(string $relation, string $class, int $count = 1): Formlet
    {

        foreach (range(1, $count) as $index) {
            $formlet = app()->make($class);
            $formlet->parent = $this->getModel();

            $formlet->name($relation);
            $formlet->setPrefix($this->prefix);

            if (!$this->formlets->has($relation)) {
                $this->formlets->put($relation, collect());
            }

            $formlet->key($this->formlets->get($relation)->count());

            $this->formlets[$relation][] = $formlet;
        }

        return $formlet;
    }

    /**
     * Get the model instance on the form builder.
     *
     * @return Model|null|mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    public function __get($name)
    {
        return $this->formlets($name);
    }

    /**
     * Returns the formlets added to this form
     *
     * @param  string|null  $name
     * @return Collection
     */
    public function formlets(string $name = null): Collection
    {

        if (is_null($name)) {
            return $this->formlets;
        }

        return $this->formlets->get($name) ?? collect();
    }

    /**
     * Return a single formlet
     *
     * @param  string  $name
     * @return null|Formlet
     */
    public function formlet(string $name): ?Formlet
    {
        return optional($this->formlets->get($name))->first();
    }

    /**
     * Map the current request for validation
     * for this formlet
     *
     * @return array
     */
    protected function mapRequest(): array
    {
        $request = $this->getFormletRequest();

        // Remove the prefix from the form post before validating
        if (!is_null($this->prefix)) {
            $request = collect($request)->mapWithKeys(function ($value, $key) {
                return [
                    $this->stripPrefix($key) => $value
                ];
            })->all();
        }
        return $request;
    }

    /**
     * Get the request which relates to this formlet
     *
     * @return array
     */
    protected function getFormletRequest(): array
    {

        if ($this->instanceName == "") {
            return $this->request->all();
        }

        // Get the key for this formlet instance
        $key = $this->transformKey(($this->prefix ? "{$this->prefix}:" : "").$this->instanceName);

        return data_get($this->request->all($key), $key) ?? [];
    }

}

