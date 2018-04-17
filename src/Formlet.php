<?php

namespace RS\Form;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use RS\Form\Concerns\{
  HasRelationships, ManagesForm, ManagesPosts, ValidatesForm
};
use RS\Form\Fields\AbstractField;

abstract class Formlet
{
    use ManagesForm,
      ValidatesForm,
      ManagesPosts,
      HasRelationships;

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

    /**
     * Whether formlets have been prepared
     *
     * @var bool
     */
    protected $prepared = false;

    abstract public function prepare(): void;

    public function initialize()
    {

        $this->attributes = collect($this->attributes);
        $this->attributes->put('action', $this->url->current());
        $this->fields = collect();
        $this->formlets = collect();
        $this->allErrors = optional($this->session->get('errors'))->getBag('default') ?? new MessageBag();
        $this->errors = new MessageBag();
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
    public function name(string $name): Formlet
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the instance name for the formlet
     *
     * @param string $name
     * @return Formlet
     */
    public function instanceName(string $name): Formlet
    {
        $this->instanceName = $name;
        return $this;
    }

    /**
     * Set the name for the formlet
     *
     * @param int $key
     * @return Formlet
     */
    public function key(int $key): Formlet
    {
        $this->key = $key;
        return $this;
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
     * Build the current form
     *
     * @return Collection
     */
    public function build(): Collection
    {

        $this->populate();

        return collect([
          'form'    => collect([
            'hidden'     => $this->getHiddenFields(),
            'attributes' => $this->attributes->sortKeys()
          ]),
          'formlet' => $this
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

    /**
     * Get the model instance on the form builder.
     *
     * @return Model|null
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * Return fields for this formlet
     *
     * @param null|string|array $name
     * @return Collection
     */
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
     * Return named field
     *
     * @param string $name
     * @return null|AbstractField
     */
    public function field(string $name): ?AbstractField
    {
        return $this->fields->get($name);
    }

    /**
     * Add a formlet(s) to form
     *
     * @param string $relation
     * @param string $class
     * @param int    $count
     * @return Formlet
     */
    public function addFormlet(string $relation, string $class, int $count = 1): Formlet
    {

        foreach (range(1, $count) as $index) {
            $formlet = app()->make($class);

            $formlet->name($relation);

            if (!$this->formlets->has($relation)) {
                $this->formlets->put($relation, collect());
            }

            $formlet->key($this->formlets->get($relation)->count());

            $this->formlets[$relation][] = $formlet;
        }

        return $formlet;
    }

    /**
     * Returns the formlets added to this form
     *
     * @param string|null $name
     * @return Collection
     */
    public function formlets(string $name = null): Collection
    {

        if (is_null($name)) {
            return $this->formlets;
        }

        return $this->formlets->get($name) ?? collect();
    }

    public function __get($name)
    {
        return $this->formlets($name);
    }

    /**
     * Return a single formlet
     *
     * @param string $name
     * @return null|Formlet
     */
    public function formlet(string $name): ?Formlet
    {
        return optional($this->formlets->get($name))->first();
    }

    /**
     * Populates all the fields
     * Populates the field from the request
     */
    protected function populate(): void
    {
        if ($this->prepared) {
            return;
        }
        $this->setFieldNames();

        $this->populateFields();

        $this->populateErrors();
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
     * @param AbstractField $field
     */
    protected function populateField(AbstractField $field): void
    {
        $value = $this->getValueAttribute($field->getInstanceName(), $field->getName());

        if (!is_null($value)) {
            $field->setValue($value);
        }
        $this->populateFieldErrors($field);
    }

    /**
     * Set all field names for this formlet
     *
     * @param string $prefix
     */
    protected function setFieldNames(string $prefix = ""): void
    {

        $this->prepare();
        $this->prepared = true;

        $prefix = $this->setFormletInstance($prefix);

        $this->fields()->each(function (AbstractField $field) {
            $this->setFieldName($field, $this->instanceName);
        });

        $this->iterateFormlets(function (Formlet $formlet) use ($prefix) {
            $formlet->setFieldNames($prefix);
        });
    }

    /**
     * Set field instance name
     *
     * @param AbstractField $field
     * @param string|null   $formletInstance
     */
    protected function setFieldName(AbstractField $field, string $formletInstance = null)
    {
        if (!is_null($formletInstance)) {
            $field->setInstanceName($formletInstance . "[" . $field->getName() . "]");
        }
    }

    /**
     * Iterate child formlets
     *
     * @param \Closure $closure
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
     * Get value from current Request
     *
     * @param $name
     * @return array|null|string
     */
    protected function request($name)
    {
        $key = is_null($name) ? null : $this->transformKey($name);
        return $this->request->input($key);
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
     * 1) Session Flash Data (Old Input)
     * 2) The request
     * 3) Model Attribute Data
     *
     * @param  string $name
     * @param  string $modelKey
     * @return mixed
     */
    protected function getValueAttribute(string $name, string $modelKey)
    {

        $old = $this->old($name);
        if (!is_null($old)) {
            return $old;
        }

        $request = $this->request($name);
        if (!is_null($request)) {
            return $request;
        }

        if ($this->model && $this->request->isMethod('GET')) {
            return $this->getModelValueAttribute($this->model, $modelKey);
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
    protected function getModelValueAttribute($model, $name)
    {
        $data = data_get($model, $this->transformKey($name));

        if (!is_null($data)) {
            return $data;
        }

        return $this->hasPivotColumns() ? data_get($model,
          $this->getPivotAccessor() . "." . $this->transformKey($name)) : null;
    }

    /**
     * Set the formlet instance based on parent formlets
     *
     * @param string $prefix
     * @return string
     */
    private function setFormletInstance(string $prefix): string
    {
        if (is_null($this->name)) {
            return "";
        }

        if ($prefix == "") {
            $formletInstance = $this->name . "[" . $this->getKey() . "]";
        } else {
            $formletInstance = $prefix . "[" . $this->name . "][" . $this->getKey() . "]";
        }

        $this->instanceName($formletInstance);

        return $formletInstance;
    }

}

