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

        $this->validateFormlet();

        $this->allErrors = $this->validateMapFormlet(collect());

        if (!$this->isAllValid() && $redirect) {
            throw (new ValidationException($this->validator, $this->buildFailedValidationResponse()));
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
    public function errors(): Collection
    {
        return $this->errors;
    }

    /**
     * Returns errors for a particular field
     * @param string $name
     * @return array|null
     */
    public function error(string $name): ?array
    {
        return $this->errors->get($name);
    }

    /**
     * Returns all the errors for this form
     */
    public function allErrors(): Collection
    {
        return $this->allErrors;
    }

    /**
     * Validates the current formlet
     * and all child formlets
     */
    protected function validateFormlet():void{

        $this->errors = $this->validateRequest();

        $this->formlets->each(function(Collection $forms){
            $forms->each(function (Formlet $formlet){
                $formlet->validateFormlet();
            });
        });

    }

    /**
     * We need to translate all the errors to their specific instance
     * We can then fill the session with these errors and the map them
     * back to the appropriate formlet
     *
     * @param Collection $errorBag
     * @return Collection
     */
    protected function validateMapFormlet(Collection $errorBag):Collection{

        return $this->validateMapFormlets($errorBag->merge($this->mapErrorsToInstances()));
    }

    /**
     *
     * @param Collection $errorBag
     * @return Collection
     */
    protected function validateMapFormlets(Collection $errorBag):Collection{

        foreach($this->formlets as $forms){
            foreach($forms as $formlet){
                $errorBag = $formlet->validateMapFormlet($errorBag);
            }
        }

        return $errorBag;

    }

    /**
     * @return Collection
     */
    protected function mapErrorsToInstances():Collection{

        if($this->instanceName ==""){
            return $this->errors;
        }

        return $this->errors->mapWithKeys(function($error, $key){

            return ["{$this->transformKey($this->instanceName)}.{$key}" => $error];
        });
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
            $this->errors = $this->errors->put($field->getName(),$error);
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



}