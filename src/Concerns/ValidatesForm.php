<?php

namespace RS\Form\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use RS\Form\Fields\AbstractField;
use RS\Form\Formlet;
use Symfony\Component\HttpFoundation\Response;

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
        $this->prepareFormlets();

        $this->errors = collect();

        $this->validateFormlets($this->formlets);

        if (!$this->isValid() && $redirect) {
            throw (new ValidationException($this->validator, $this->buildFailedValidationResponse()));
        }
    }

    protected function validateFormlets(Collection $formlets){
        foreach ($formlets as $forms) {
            foreach ($forms as $formlet) {

                $errors = $formlet->validateRequest();

                if (count($errors) > 0) {
                    $this->errors = $this->errors->merge($errors);
                }

                $this->validateFormlets($formlet->formlets());
            }
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
    public function errors(): Collection
    {
        return collect($this->errors);
    }

    /**
     * Validate the given request with the given rules.
     *
     * @return array
     */
    public function validateRequest(): Collection
    {

        $request = $this->request->input($this->transformKey($this->instanceName)) ?? [];

        $this->validator = $this->getValidationFactory()->make(
          $request,
          $this->rules(),
          $this->messages(),
          $this->attributes()
        );

        if ($this->validator->fails()) {

            return collect($this->validator->errors()->getMessages())->mapWithKeys(function($error, $key){
                return ["{$this->transformKey($this->instanceName)}.{$key}" => $error];
            });
        }

        return collect();
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
     * @param               $key
     * @return void
     */
    protected function populateErrors(AbstractField $field, $key): void
    {
        $errors = $this->errors();
        if ($error = $errors->get($key)) {
            $field->setErrors(collect($error));
        }
    }

    /**
     * Create the response for when a request fails validation.
     *
     * @return Response
     */
    protected function buildFailedValidationResponse(): Response
    {
        if ($this->request->expectsJson()) {
            return new JsonResponse($this->errors, 422);
        }

        return redirect()->to($this->getRedirectUrl())
          ->withInput($this->request->input())
          ->withErrors($this->errors->toArray());
    }

}