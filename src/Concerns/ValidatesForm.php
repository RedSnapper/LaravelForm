<?php

namespace RS\Form\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use RS\Form\Fields\AbstractField;
use RS\Form\Formlet;

trait ValidatesForm
{
    /**
     * An errors for the formlet.
     *
     * @var MessageBag
     */
    protected $errors;

    /**
     * All errors for the form.
     *
     * @var MessageBag
     */
    protected $allErrors;

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
     * @param  bool  $redirect
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate($redirect = true)
    {
        $this->populate();

        $this->validateFormlet();

        $this->allErrors = $this->validateMapFormlet(new MessageBag());

        if (!$this->isAllValid() && $redirect) {

            $this->failedValidation();
        }
    }

    /**
     * Is the current formlet valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return count($this->errors) == 0;
    }

    /**
     * Is the current formlet valid
     *
     * @return bool
     */
    public function isAllValid(): bool
    {
        return count($this->allErrors) == 0;
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
     * Returns all the errors for this formlet
     */
    public function errors(): MessageBag
    {
        return $this->errors;
    }

    /**
     * Returns errors for a particular field
     *
     * @param  string  $name
     * @return array|null
     */
    public function error(string $name): ?array
    {
        return $this->errors->get($name);
    }

    /**
     * Returns all the errors for this form
     */
    public function allErrors(): MessageBag
    {
        return $this->allErrors;
    }

    /**
     * Validates the current formlet
     * and all child formlets
     */
    protected function validateFormlet(): void
    {
        $this->errors = $this->validateRequest();

        $this->iterateFormlets(function (Formlet $formlet) {
            $formlet->validateFormlet();
        });
    }

    /**
     * We need to translate all the errors to their specific instance
     * We can then fill the session with these errors and the map them
     * back to the appropriate formlet
     *
     * @param  MessageBag  $errorBag
     * @return MessageBag
     */
    protected function validateMapFormlet(MessageBag $errorBag): MessageBag
    {
        $array = $this->mapErrorsToInstances();

        $errorBag = $errorBag->merge($array);

        return $this->validateMapFormlets($errorBag);
    }

    /**
     * @param  MessageBag  $errorBag
     * @return MessageBag
     */
    protected function validateMapFormlets(MessageBag $errorBag): MessageBag
    {

        foreach ($this->formlets as $forms) {
            foreach ($forms as $formlet) {
                $errorBag = $formlet->validateMapFormlet($errorBag);
            }
        }

        return $errorBag;
    }

    /**
     * @return array
     */
    protected function mapErrorsToInstances(): array
    {

        if (is_null($this->instanceName)) {
            return $this->errors->messages();
        }

        return collect($this->errors->messages())->mapWithKeys(function ($error, $key) {
            return ["{$this->transformKey($this->instanceName)}.{$key}" => $error];
        })->all();
    }

    /**
     * Validate the given request with the given rules.
     *
     * @return Collection
     */
    protected function validateRequest(): MessageBag
    {
        $validator = $this->getValidatorInstance();

        return $validator->messages();
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
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {

        $validator = $this->getValidationFactory()->make(
          $this->input->all(),
          $this->rules(),
          $this->messages(),
          $this->attributes()
        );

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        return $validator;
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
     * @param  AbstractField  $field
     * @return void
     */
    protected function populateFieldErrors(AbstractField $field): void
    {
        $key = $this->transformKey($this->stripPrefix($field->getInstanceName()));

        $errors = $this->allErrors();
        if ($error = $errors->get($key)) {
            $field->setErrors(collect($error));
        }
    }

    /**
     * Populates each formlet with the errors
     * found in the session for this formlet
     * Uses the formlets rules method to find
     * which errors should be added to the formlet
     */
    protected function populateErrors()
    {

        $formletKey = $this->transformKey($this->instanceName);

        $rules = $this->getValidatorInstance()->getRules();

        $mappedRules = collect($rules)->keys()->mapWithKeys(function ($rule) use ($formletKey) {
            return [$formletKey == "" ? $rule : "$formletKey.$rule" => $rule];
        });

        $errors = collect($this->allErrors()->messages())->only($mappedRules->keys())->mapWithKeys(function (
          $value,
          $key
        ) use ($mappedRules) {
            return [$mappedRules->get($key) => $value];
        });

        $this->errors = $this->errors->merge($errors->all());

        $this->iterateFormlets(function (Formlet $formlet) {
            $formlet->populateErrors();
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(): void
    {
        throw ValidationException::withMessages($this->allErrors->messages())
          ->errorBag($this->getErrorBagName())
          ->redirectTo($this->getRedirectUrl());
    }

    /**
     * Get the error bag name for the form
     *
     * @return string
     */
    protected function getErrorBagName(): string
    {
        return $this->prefix ?? "default";
    }

    /**
     * Remove the formlet prefix from a string
     *
     * @param  string  $name
     * @return string
     */
    private function stripPrefix(string $name): string
    {
        return $this->prefix ? Str::replaceFirst("{$this->prefix}:", "", $name) : $name;
    }

}