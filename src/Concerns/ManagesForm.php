<?php

namespace RS\Form\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use RS\Form\Fields\AbstractField;
use RS\Form\Fields\Hidden;
use RS\Form\Formlet;

trait ManagesForm
{
    /**
     * Form method
     *
     * @var string
     */
    protected $method = 'POST';

    /**
     * Form attributes
     *
     * @var Collection
     */
    protected $attributes = [
      'method' => 'POST',
      'accept-charset' => 'UTF-8',
      'enctype' => 'multipart/form-data'
    ];

    /**
     * The form methods that should be spoofed, in uppercase.
     *
     * @var array
     */
    protected $spoofedMethods = ['DELETE', 'PATCH', 'PUT'];


    public function getAttribute(string $name)
    {
        return $this->attributes->get($name);
    }

    public function setAttribute(string $attribute, $value = null): Formlet
    {
        $this->attributes->put($attribute, $value ?? $attribute);
        return $this;
    }

    /**
     * Set the method for the form
     * @param string $name
     * @return Formlet
     */
    public function method(string $name): Formlet
    {
        $method = strtoupper($name);

        $this->method = $method;

        // If the method is a spoofed method then we need to set the attribute
        // to be a POST
        if(in_array($method,$this->spoofedMethods)){
            $method = "POST";
        }

        $this->setAttribute('method',$method);

        return $this;
    }

    /**
     * Returns the current method of the form
     * @return string
     */
    protected function getMethod():string{
        return $this->method;
    }

    /**
     * Set the route for the form
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     * @return Formlet
     */
    public function route(string $name, $parameters = [], $absolute = true): Formlet
    {
        $this->setAttribute('action', $this->url->route($name, $parameters, $absolute));

        $routes = Route::getRoutes();

        $route = $routes->getByName($name);

        $this->method($route->methods()[0]);

        return $this;
    }

    /**
     * Returns the hidden fields for the form
     * @return Collection
     */
    protected function getHiddenFields(): Collection
    {
        $hidden = collect([
          'token' => $this->token()
        ]);

        $method = $this->getMethod();

        // If the HTTP method is in this list of spoofed methods, we will attach the
        // method spoofer hidden input to the form. This allows us to use regular
        // form to initiate PUT and DELETE requests in addition to the typical.
        if (in_array($method, $this->spoofedMethods)) {
            $hidden->put('method', (new Hidden('_method'))->setValue($method));
        }

        return $hidden;
    }

    /**
     * CSRF field
     * @return AbstractField
     */
    protected function token(): AbstractField
    {
        return (new Hidden('_token'))->setValue($this->session->token());
    }

}