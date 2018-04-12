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
     * An errors for the formlet.
     *
     * @var Collection
     */
    protected $errors;

    /**
     * All errors for the form.
     *
     * @var Collection
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
     * @param bool $redirect
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate($redirect = true)
    {
        $this->populate();

        $this->allErrors = $this->validateFormlet(collect());

        if (!$this->isAllValid() && $redirect) {
            throw (new ValidationException($this->validator, $this->buildFailedValidationResponse()));
        }
    }

    protected function validateFormlet(Collection $errorBag):Collection{

        $this->errors = $this->validateRequest();

        $errors = $this->mapErrorsToInstances();

        if (count($errors) > 0) {
            $errorBag = $errorBag->merge($errors);
        }

        return $this->validateFormlets($errorBag);

    }

    protected function validateFormlets(Collection $errorBag):Collection{
        foreach ($this->formlets as $forms) {
            foreach ($forms as $formlet) {

                $errorBag = $formlet->validateFormlet($errorBag);
            }
        }
        return $errorBag;
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
    public function errors(): Collection
    {
        return $this->errors;
    }

    /**
     * Returns all the errors for this form
     */
    public function allErrors(): Collection
    {
        return $this->allErrors;
    }

    /**
     * Validate the given request with the given rules.
     *
     * @return Collection
     */
    protected function validateRequest(): Collection
    {

        $request = $this->request($this->instanceName) ?? [];

        $this->validator = $this->getValidationFactory()->make(
          $request,
          $this->rules(),
          $this->messages(),
          $this->attributes()
        );

        if ($this->validator->fails()) {
            return collect($this->validator->errors()->getMessages());
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
        $errors = $this->allErrors();
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
            return new JsonResponse($this->allErrors, 422);
        }

        return redirect()->to($this->getRedirectUrl())
          ->withInput($this->request->input())
          ->withErrors($this->allErrors->toArray());
    }

    protected function mapErrorsToInstances():Collection{
        return $this->errors->mapWithKeys(function($error, $key){

            if($this->instanceName ==""){
                return [$key=>$error];
            }
            return ["{$this->transformKey($this->instanceName)}.{$key}" => $error];
        });
    }

}