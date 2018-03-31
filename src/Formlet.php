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

    abstract public function prepare(): void;

    public function initialize()
    {

        $this->attributes = collect($this->attributes);
        $this->attributes->put('action', $this->url->current());
        $this->fields = collect();
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
            'formlets' => [
                'main' => ['fields' => $this->fields()]
            ]
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
     * Populates all the fields
     * Populates the field from the request
     */
    protected function populate(): void
    {
        $this->fields->each(function (AbstractField $field, $key) {
            if ($value = $this->getValueAttribute($key)) {
                $field->setValue($value);
            }
            $this->populateErrors($field, $key);
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
     * @return mixed
     */
    public function getValueAttribute($name)
    {

        $old = $this->old($name);
        if (!is_null($old)) {
            return $old;
        }

        if ($request = $this->request($name)) {
            return $request;
        }

        if (isset($this->model)) {
            return $this->getModelValueAttribute($name);
        }

        return null;
    }

    /**
     * Get a value from the session's old input.
     *
     * @param  string $name
     * @return mixed
     */
    public function old($name)
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