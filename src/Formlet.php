<?php
declare(strict_types=1);

namespace RS\Form;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RS\Form\Concerns\ManagesForm;
use RS\Form\Fields\AbstractField;

abstract class Formlet
{
    use ManagesForm;

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

    abstract public function prepare(): void;

    public function initialize()
    {

        $this->attributes = collect($this->attributes);
        $this->attributes->put('action', $this->url->current());
        $this->fields = collect();

        $this->prepare();

        $this->populate();
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

        return collect([
          '_hidden' => $this->getHiddenFields()
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
        $this->fields->each(function ($field, $key) {

            if ($request =  $this->request($key)) {
                $field->setValue($request);
            }
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

}