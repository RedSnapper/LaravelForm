<?php

namespace RS\Form\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use RS\Form\Fields\AbstractField;

trait ValidatesForm
{
    /**
     * An errors for the form.
     *
     * @var Collection
     */
    protected $errors;

    /**
     * Validator for the form.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * The route to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectRoute;

    /**
     * Validation rules that apply to the form.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Validate this form
     *
     * @param bool $redirect
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate($redirect = true)
    {
        $this->errors = $this->_validateRequest();

        if (!$this->isValid() && $redirect) {
            throw (new ValidationException($this->validator))
                    ->redirectTo($this->getRedirectUrl());
        }
    }

    /**
     * Is the current form valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return count($this->errors) == 0;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Returns all the errors for this form
     */
    public function getErrors(): Collection
    {
        return collect($this->errors);
    }

    /**
     * Validate the given request with the given rules.
     *
     * @return array
     */
    public function _validateRequest(): array
    {
        $this->validator = $this->getValidationFactory()->make($this->request->all(), $this->rules(), $this->messages(),
          $this->attributes());

        if ($this->validator->fails()) {
            return $this->validator->errors()->getMessages();
        }

        return [];
    }

    /**
     * Get a validation factory instance.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidationFactory(): \Illuminate\Contracts\Validation\Factory
    {
        return app(\Illuminate\Contracts\Validation\Factory::class);
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {

        if ($this->redirectRoute) {
            return $this->url->to($this->redirectRoute);
        }

        return $this->url->previous();
    }

    /**
     * Populate field with errors
     *
     * @param AbstractField $field
     * @param $key
     * @return void
     */
    protected function populateErrors(AbstractField $field, $key): void
    {
        $errors = $this->getErrors();
        if($error = $errors->get($key)){
            $field->setErrors(collect($error));
        }
    }

}