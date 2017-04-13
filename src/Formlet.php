<?php

namespace RS\Form;

use RS\Form\Fields\AbstractField;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

abstract class Formlet {

	use Concerns\ManagesForm;

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
	 * @var Request
	 */
	public $request;

	/**
	 * View for formlet
	 *
	 * @var string
	 */
	public $formletView = "form.auto";

	/**
	 * View for composite formlets
	 *
	 * @var string
	 */
	public $compositeView;

	/**
	 * Main form view
	 *
	 * @var string
	 */
	public $formView;

	/**
	 * All fields that are added.
	 *
	 * @var AbstractField[]
	 */
	protected $fields = [];

	/**
	 * The current model instance for the form.
	 *
	 * @var mixed
	 */
	protected $model;

	/**
	 * Fields which will not populate
	 *
	 * @var array
	 */
	protected $guarded = [];

	/**
	 * Formlets attached to this form
	 *
	 * @var Formlet[]
	 */
	protected $formlets = [];

	/**
	 * Validator for this form
	 *
	 * @var Validator
	 */
	protected $validator;

	/**
	 * Formlet name.
	 *
	 * @var string
	 */
	protected $name = "";

	/**
	 * Formlet key.
	 *
	 * @var int|null
	 */
	protected $key;

	/**
	 * If there are multiple of this formlet we need to include
	 * the key in the formlet
	 *
	 * @var bool
	 */
	protected $multiple = false;

	/**
	 * Extra view data
	 *
	 * @var array
	 */
	protected $data = [];


	abstract public function prepareForm();

	public function rules(): array {
		return [];
	}

	/**
	 * Add a piece of data to the view.
	 *
	 * @param  string|array $key
	 * @param  mixed        $value
	 * @return $this
	 */
	public function with($key, $value = null) {
		if (is_array($key)) {
			$this->data = array_merge($this->data, $key);
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}

	public function getData(string $name){
		return data_get($this->data, $name);
	}

	/**
	 * There are multiple of this formlet
	 *
	 * @return bool
	 */
	public function isMultiple(): bool {
		return $this->multiple;
	}

	/**
	 * @param bool $multiple
	 */
	public function setMultiple(bool $multiple = true) {
		$this->multiple = $multiple;
	}

	/**
	 * Get the key for the formlet
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Add formlet to this formlet
	 *
	 * @param string $name
	 * @param string $class
	 * @return Formlet
	 */
	public function addFormlet(string $name, string $class): Formlet {
		$formlet = app()->make($class);
		$formlet->name = $name;
		$this->formlets[$name] = $formlet;
		return $formlet;
	}

	public function addFormlets(string $name, string $class): Formlet {
		$formlet = app()->make($class);
		$formlet->name = $name;
		$this->formlets[$name][] = $formlet;
		$formlet->setMultiple();
		return $formlet;
	}


	/**
	 * Add subscribers to this formlet
	 *
	 * @param string $name
	 * @param string $class
	 * @param BelongsToMany $builder
	 */
	public function addSubscribers(string $name, string $class, BelongsToMany $builder, $items = null) {

		$items = $items ?? $builder->getRelated()->all();
		$models = $builder->get();

		foreach ($items as $item) {
			$formlet = app()->make($class);
			$model = $this->getModelByKey($item->getKey(), $models);
			$this->addSubscriberFormlet($formlet, $name, $item, $model);
		}
	}

	/**
	 * Get model from a collection of models for a given key
	 *
	 * @param int        $key
	 * @param Collection $models
	 * @param string     $keyName
	 * @return Model|null
	 */
	protected function getModelByKey(int $key, Collection $models, $keyName = "id") {
		return $models->where($keyName, $key)->first();
	}

	protected function addSubscriberFormlet(Formlet $formlet, string $name, Model $subscriber, Model $model = null) {
		$formlet->setKey($subscriber->getKey());
		$formlet->with($name, $subscriber);
		$formlet->setModel($model);
		$formlet->setName($name);
		$formlet->setMultiple();
		$this->formlets[$name][] = $formlet;
	}

	/**
	 * Get the subscriber fields from the request for a given key
	 *
	 * @param string $field
	 * @param string $subscriber
	 * @return Collection
	 */
	public function getSubscriberFields(string $field , string $subscriber ="subscriber"){

		$collection = new Collection($this->fields($field));

		$collection = $collection->filter(function ($value) use($subscriber) {
			return isset($value[$subscriber]);
		})->map(function ($item) use($subscriber) {
			array_forget($item,$subscriber);
			return $item;
		});

		return $collection;
	}

	protected function isValid() {

		$errors = [];

		if (count($this->formlets) == 0) {
			$errors = $this->validate($this->request->all(), $this->rules());
		}

		foreach ($this->formlets as $formlet) {

			if (is_array($formlet)) {

				foreach ($formlet as $f) {
					$request = $this->request->input($f->getName() . "." . $f->getKey()) ?? [];
					$errors = array_merge($errors, $f->validate($request, $f->rules()));
				}
			} else {
				$request = $this->request->get($formlet->getName()) ?? [];
				$errors = array_merge($errors, $formlet->validate($request, $formlet->rules()));
			}
		}

		return $this->redirectIfErrors($errors);
	}

	protected function redirectIfErrors(array $errors) {

		if (count($errors)) {
			throw new ValidationException($this->validator, $this->buildFailedValidationResponse(
			  $errors
			));
		}

		return true;
	}

	/**
	 * Create the response for when a request fails validation.
	 *
	 * @param  array $errors
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function buildFailedValidationResponse(array $errors) {
		if ($this->request->expectsJson()) {
			return new JsonResponse($errors, 422);
		}

		return redirect()->to($this->getRedirectUrl())
		  ->withInput($this->request->input())
		  ->withErrors($errors);
	}

	/**
	 * Validate the given request with the given rules.
	 *
	 * @param  array $request
	 * @param  array $rules
	 * @param  array $messages
	 * @param  array $customAttributes
	 * @return array
	 */
	public function validate(array $request, array $rules, array $messages = [], array $customAttributes = []) {
		$this->validator = $this->getValidationFactory()->make($request, $rules, $messages, $customAttributes);

		$this->addCustomValidation($this->validator);

		if ($this->validator->fails()) {
			return $this->formatValidationErrors($this->validator);
		}
		return [];
	}

	public function addCustomValidation(Validator $validator) {
	}

	public function renderWith($modes) {
		return $this->create($modes)->render();
	}

	public function store() {

		$this->prepare();
		if ($this->isValid()) {
			return $this->persist();
		}
	}

	public function persist(): Model {
		if (isset($this->model)) {
			$this->model = $this->model->create($this->fields());
		}
		return $this->model;
	}

	public function delete($key): Model {
		return $this->model->destroy($key);
	}

	public function edit(): Model {
		if (isset($this->model)) {
			$this->model->fill($this->fields());
			$this->model->save();
		}
		$this->editFormlets();
		return $this->model;
	}

	private function editFormlets() {
		foreach($this->formlets as $name => $formlet) {
			if(is_array($formlet)) {
				foreach($formlet as $formletInstance) {
					$formletInstance->edit();
				}
			} else {
				$formlet->edit();
			}
		}
	}

	public function update() {
		$this->prepare();
		if ($this->isValid()) {
			return $this->edit();
		}
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	public function setKey($key) {
		$this->key = $key;
		if (isset($this->model) && isset($this->key)) {
			$this->model = $this->model->find($this->key);
		}
		return $this;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name) {
		$this->name = $name;
	}

	/**
	 * Set the model instance on the form builder.
	 *
	 * @param  mixed $model
	 * @return Formlet
	 */
	public function setModel($model) {
		$this->model = $model;
		return $this;
	}

	/**
	 * Add a field to the formlet
	 *
	 * @param AbstractField $field
	 */
	public function add(AbstractField $field) {
		$this->fields[] = $field;
	}

	/**
	 * Fetch all fields from the form.
	 * I want to change this so that we get stuff for 'this' formlet also, rather than all.
	 *
	 * @return array
	 */
	public function fields($name = null) {
		if (is_null($name)) {
			if ($this->name != "") {
				return $this->request->input($this->name) ?? [];
			} else {
				return $this->request->all();
			}
		} else {
			return $this->request->input($name) ?? [];
		}
	}

	protected function prepare() {
		$this->prepareForm();

		$this->prepareFormlets($this->formlets);

		if (count($this->formlets) && count($this->fields)) {
			$this->setName('base');
			$this->formlets['base'] = clone $this;
			$this->formlets['base']->formlets = [];
		}

		foreach ($this->fields as $field) {
			$field->setFieldName($this->getFieldPrefix($field->getName()));
		}
	}

	protected function prepareFormlets(array $formlets) {

		foreach ($formlets as $name => $formlet) {
			if (is_array($formlet)) {
				$this->prepareFormlets($formlet);
			} else {
				$formlet->prepare();
			}
		}
	}

	public function render() {
		$this->prepare();
		$this->populate();

		$data = [
		  'form'       => $this->renderFormlets(),
		  'attributes' => $this->attributes,
		  'hidden'     => $this->getFieldData($this->hidden)
		];
//dd($data);
		return view($this->formView, $data);
	}

	protected function renderFormlets($formlets = []) {

		if (count($this->formlets)) {

			foreach ($this->formlets as $name => $formlet) {
				if (is_array($formlet)) {
					foreach ($formlet as $form) {
						$formlets[$name][] = $form->renderFormlets();
					}
				} else {
					$formlets[$name] = $formlet->renderFormlets();
				}
			}

			if ($this->compositeView) {
				return view($this->compositeView, compact('formlets'));
			} else {
				return $formlets;
			}
		} else {
			return $this->renderFormlet();
		}
	}

	public function renderFormlet() {

		$errors = $this->getErrors();

		$data = [
		  'fields' => $this->getFieldData($this->fields),
			'model'  => $this->getModel()
		];

		$data = array_merge($data, $this->data);

		return view($this->formletView, $data)->withErrors($errors);
	}

	protected function getFieldData(array $fields): array {
		return array_map(function (AbstractField $field) {
			return $field->getData();
		}, $fields);
	}

	protected function setFieldNames() {
		foreach ($this->fields as $field) {
			$field->setFieldName($this->getFieldPrefix($field->getName()));
		}
	}

	/**
	 * Get the value that should be assigned to the field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @return mixed
	 */
	public function getValueAttribute($name, $value = null, $default = null) {

		// Field should not ne populated from post or from the model
		if (in_array($name, $this->guarded)) {
			return $value;
		}

		if (is_null($name)) {
			return $value;
		}

		if (!is_null($this->old($name)) && $name != '_method') {
			return $this->old($name);
		}

		if (!is_null($value)) {
			return $value;
		}

		if (isset($this->model)) {
			return $this->getModelValueAttribute($name) ?? $default;
		}

		return $default;
	}

	/**
	 * Get a value from the session's old input.
	 *
	 * @param  string $name
	 * @return mixed
	 */
	public function old($name) {
		if (isset($this->session)) {
			return $this->session->getOldInput($this->transformKey($this->getFieldPrefix($name)));
		}
	}

	/**
	 * Transform key from array to dot syntax.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	protected function transformKey($key) {
		return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
	}

	/**
	 * Get the model value that should be assigned to the field.
	 *
	 * @param  string $name
	 * @return mixed
	 */
	protected function getModelValueAttribute($name) {
		$name = $this->transformKey($name);
		if ($name == "") {
			return $this->model;
		}

		if($this->isMultiple()){
			return data_get($this->model, "pivot.$name") ?? data_get($this->model, $name);
		}

		return data_get($this->model, $name);
	}

	protected function setFieldValue(AbstractField $field): AbstractField {

		$name = $field->getName();
		$value = $field->getValue();
		$default = $field->getDefault();

		$value = $this->getValueAttribute($name, $value, $default);
		$field->setValue($value);

		return $field;
	}

	/**
	 * Returns any errors from the session
	 *
	 * @return array|MessageBag
	 */
	protected function getErrors() {
		$errors = $this->session->get('errors');

		return is_null($errors) ? [] : $errors->getBag('default');
	}

	/**
	 * Get the check state for a checkbox input.
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  bool   $checked
	 * @return bool
	 */
	protected function getCheckboxCheckedState($name, $value, $checked) {

		if (isset($this->session) && !$this->oldInputIsEmpty() && is_null($this->old($name))) {
			return false;
		}

		if ($this->missingOldAndModel($name)) {
			return $checked;
		}

		$posted = $this->getValueAttribute("", $checked);

		if (is_array($posted)) {
			return in_array($value, $posted);
		} elseif ($posted instanceof Collection) {
			return $posted->contains('id', $value);
		} else {
			return (bool)$posted;
		}
	}

	protected function populate() {

		$this->transformGuardedAttributes();

		foreach ($this->fields as $field) {
			if (is_null($type = $field->getType())) {
				$this->setFieldValue($field);
			} else {
				$this->$type($field);
			}
		}

		$this->populateFormlets($this->formlets);
	}

	protected function populateFormlets(array $formlets = []) {
		foreach ($formlets as $formlet) {
			if (is_array($formlet)) {
				$this->populateFormlets($formlet);
			} else {
				$formlet->populate();
			}
		}
	}

	protected function transformGuardedAttributes() {
		$this->guarded = array_map(function ($item) {
			return $this->getFieldPrefix($item);
		}, $this->guarded);
	}

	/**
	 * Determine if old input or model input exists for a key.
	 *
	 * @param  string $name
	 * @return bool
	 */
	protected function missingOldAndModel($name) {
		return (is_null($this->old($name)) && is_null($this->getModelValueAttribute("")));
	}

	/**
	 * Determine if the old input is empty.
	 *
	 * @return bool
	 */
	public function oldInputIsEmpty() {
		return (isset($this->session) && count($this->session->getOldInput()) == 0);
	}

	protected function checkable(AbstractField $field) {

		$name = $field->getName();
		$value = $field->getValue();
		$default = $field->getDefault();

		$field->setValue($value);

		$checked = $this->getCheckboxCheckedState($name, $value, $default);

		if ($checked) {
			$field->setAttribute('checked');
		}

		return $field;
	}

	/**
	 * Set url generator
	 *
	 * @param UrlGenerator $url
	 * @return $this
	 */
	public function setUrlGenerator(UrlGenerator $url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * Set request on form.
	 *
	 * @param Request $request
	 * @return $this
	 */
	public function setRequest(Request $request) {
		$this->request = $request;
		return $this;
	}

	/**
	 * Set the session store for formlets
	 *
	 * @param Session $session
	 */
	public function setSessionStore(Session $session) {
		$this->session = $session;
	}

	/**
	 * Get a validation factory instance.
	 *
	 * @return \Illuminate\Contracts\Validation\Factory
	 */
	protected function getValidationFactory() {
		return app(Factory::class);
	}

	//nested formlets need nested name.
	public function getModel($name = "") {
		if (isset($this->formlets[$name])) {
			return $this->formlets[$name]->getModel();
		} else {
			return $this->model;
		}
	}

	//nested formlets need nested name.
	protected function getFormlet($name = "") {
		return @$this->formlets[$name];
	}

	/**
	 * Format the validation errors to be returned.
	 *
	 * @param  \Illuminate\Contracts\Validation\Validator $validator
	 * @return array
	 */
	protected function formatValidationErrors(Validator $validator) {

		$errors = collect($validator->errors()->getMessages());

		$errors = $errors->keyBy(function ($item, $key) {
			return $this->getFieldPrefix($key);
		});

		return $errors->all();
	}

	protected function getFieldPrefix($field) {

		$name = $this->getName();

		if ($name == "") {
			return $field;
		}

		$instance = $this->getFieldInstance();

		$parts = explode('[', $field);

		if (count($parts) == 1) {
			return "{$name}{$instance}[$field]";
		}

		$field = array_pull($parts, 0);
		$extra = implode('[', $parts);

		return "{$name}[$field]{$instance}[$extra";
	}

	protected function getFieldInstance(): string {

		//If there are multiples of this formlet we need to include
		//the key in the formlet

		if ($this->isMultiple()) {
			return "[" . $this->getKey() . "]";
		}

		return "";
	}

	/**
	 * Get the URL we should redirect to.
	 *
	 * @return string
	 */
	protected function getRedirectUrl() {
		return app(UrlGenerator::class)->previous();
	}

}