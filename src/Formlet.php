<?php

namespace RS\Form;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RS\Form\Concerns\ManagesForm;
use RS\Form\Fields\AbstractField;
use RS\Form\Fields\Checkbox;

abstract class Formlet {
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

	public function getData(string $name) {
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
	 * Get the subscriber fields from the request for a given key
	 *
	 * @param string   $key
	 * @return Collection
	 */
	public function getSubscriberFields(string $key) : Collection {
		$fieldsToCollect = array_keys($this->fields($key));
		return new Collection($fieldsToCollect);
	}

	/**
	 * Add subscribers to this formlet.
	 * We are going to construct an array of subscriber formlets using $class as a template.
	 * using builder->related->all as the basic list of options, and builder as the selected list of options.
	 *
	 * @param string                $name
	 *   name is an identifier used to compose the data / fieldnames.
	 *   e.g. 'activities'. (from http://localhost/role/3/edit)
	 * @param string                $formletClass
	 *   class is the subscriber formlet to be used, eg App\Http\Formlets\RoleActivityFormlet::class
	 * @param BelongsToMany         $builder
	 *   builder is a BelongsToMany from the base model, eg.
	 *   $this->model->activities() (where the model is eg /App/Models/Role)
	 * @param Collection|array|null $subscribeOptions
	 */
	public function addSubscribers(string $name, string $formletClass, BelongsToMany $builder, $subscribeOptions = null) {

		$subscribeOptions = $subscribeOptions ?? $builder->getRelated()->all();
		$subscribedModels = $builder->get();

		foreach ($subscribeOptions as $option) {
			$formlet = app()->make($formletClass);
			$subscribed = $this->getModelByKey($option->getKey(), $subscribedModels);
			$this->addSubscriberFormlet($formlet, $name, $option, $subscribed);
		}
	}

	/**
	 * The point of this is to provide a means of setting up a formlet
	 * that represents a ManyToMany 'sync'.
	 * It's not necessary to use this method, but it's useful if the view is not trivial.
	 * It's separated from the addSubscribers so that it may be overloaded by the concrete class.
	 *
	 * @param Formlet    $formlet
	 * @param string     $name
	 * @param Model      $option
	 * @param Model|null $subscribed
	 */
	protected function addSubscriberFormlet(Formlet $formlet, string $name, Model $option, Model $subscribed = null) {
		$dataName = $formlet->subscriber ?? $name;
		$formlet->setKey($option->getKey());
		$formlet->with($dataName, $subscribed);
		$formlet->with('option', $option);
		$formlet->setName($name);
		$formlet->setMultiple();
		$this->formlets[$name][] = $formlet;
	}

	/**
	 * Add multiple formlets to this formlet.
	 *
	 * @param string $name
	 * @param string $formletClass
	 * @return Formlet
	 */
	public function addFormlets(string $name, string $formletClass): Formlet {
		$formlet = app()->make($formletClass)->with($this->data);
		$formlet->name = $name;
		$this->formlets[$name][] = $formlet;
		$formlet->setMultiple();
		return $formlet;
	}

	/**
	 * Add formlet to this formlet
	 *
	 * @param string $name
	 * @param string $formletClass
	 * @return Formlet
	 */
	public function addFormlet(string $name, string $formletClass): Formlet {
		$formlet = app()->make($formletClass)->with($this->data);
		$formlet->name = $name;
		$this->formlets[$name] = $formlet;
		return $formlet;
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
		if (!$this->prepare()) {
			return null;
		}
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
			$fieldsToSave = $this->fields();
			$this->model->fill($fieldsToSave);
			$this->model->save();
		}
		return $this->model;
	}

	public function update() {
		if (!$this->prepare()) {
			return $this->model;
		}
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
	public function fields($name = null): array {
		if (is_null($name)) {
			if ($this->name != "") {
				$fields = $this->request->input($this->name) ?? [];
			} else {
				$fields = $this->request->all();
			}
		} else {
			$fields = $this->request->input($name) ?? [];
		}
		return $this->rationalise($fields);
	}

	/**
	 * We are doing this to include Checkboxes/'checkable' which otherwise aren't being updated because they aren't being
	 * posted. Be aware that if your checkbox field is not nullable, you will need to cast it or use a mutator.
	 *
	 * @param $postedFields array
	 * @return array
	 */
	private function rationalise(array $postedFields): array {
		$result = $postedFields;
		foreach ($this->fields as $field) {
			if (is_a($field, Checkbox::class)) {
				$modelName = $field->getName();
				if (!empty($modelName)) {
					$result[$modelName] = @$postedFields[$modelName];
				}
			}
		}
		return $result;
	}

	/**
	 * TODO: This uses a concrete input name: '_fn' - maybe that's okay, but maybe we should think about it more.
	 *
	 * @return bool
	 */
	protected function doFunction(): bool {
		$parameters = $this->request->request; //Collection of posted parameters.
		if ($parameters->has("_fn")) {
			$function = (string)$parameters->get("_fn");
			if (!preg_match("/\A[a-z][A-Za-z\d]*\z/", $function)) {
				return false;
			}
			if (is_callable([$this, $function])) {
				return $this->$function();
			}
		}
		return true;
	}

	protected function prepare(Formlet $parent = null): bool {
		$this->name = $this->name == "" ? (is_null($parent) ? "base" : "$parent->name.base") : $this->name;

		if (!$this->doFunction()) {
			return false;
		}
		$this->prepareForm();

		$this->prepareFormlets($this->formlets);

		//We only need a name if we have formlets and we have fields.
		if (count($this->formlets) && count($this->fields)) {
			$this->formlets[$this->name] = clone $this;
			$this->formlets[$this->name]->formlets = [];
		} elseif (is_null($parent)) {
			$this->name = "";
		}

		foreach ($this->fields as $field) {
			$field->setFieldName($this->getFieldPrefix($field->getName()));
		}
		return true;
	}

	protected function prepareFormlets(array $formlets) {

		foreach ($formlets as $name => $formlet) {
			if (is_array($formlet)) {
				$this->prepareFormlets($formlet);
			} else {
				$formlet->prepare($this);
			}
		}
	}

	public function render() {
		if (!$this->prepare()) {
			return null;
		}
		$this->populate();

		$data = [
			'form'       => $this->renderFormlets(),
			'attributes' => $this->attributes,
			'hidden'     => $this->getFieldData($this->hidden)
		];

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

		// Field should not be populated from post or from the model
		if (in_array($name, $this->guarded)) {
			return $value;
		}

		//If there is no name, then we may as well just use what value we have.
		if (is_null($name)) {
			return $value;
		}

		//Let's NOT use the same method twice in a row [eg this->old()], if we can help it, but use a temporary variable instead..
		//TODO: Find out exactly what _method is, and cf. it with _fn.
		$oldValue = $this->old($name);
		if (!is_null($oldValue) && $name != '_method') {
			return $oldValue;
		}

		//So name has returned nothing meaningful so far. Let's pass back the value if it is set.
		if (!is_null($value)) {
			return $value;
		}

		//Nothing there either. Let's try the model.
		//Except that the model is currently un-applied.
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
			$prefix = $this->getFieldPrefix($name);
			$transform = $this->transformKey($prefix);
			$old = $this->session->getOldInput($transform);
			return $old;
			// If we like guard clauses and we want to avoid nested ifs, we should avoid nesting methods too,
			// even though it may mean using temporary variables.
			// PhpStorm's ability to trace through nested methods is not great.
			// Likewise it sucks at tracing immediate return points.
			// return $this->session->getOldInput(
			//	$this->transformKey(
			//		$this->getFieldPrefix($name)
			//	)
			//);
		}
		//one of the challenges of using Guard Clauses vs. Single-Entry Single-Exit (SESE)
		// is that it is easy to forget to return stuff.
		return null;
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

		if ($this->isMultiple()) {
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

	protected function checkable(AbstractField $field) {

		/**
		 * So, 'checkable' fields (Checkbox) don't return anything in the post unless they are checked.
		 * That means that the value we want to give them is if they are checked, regardless of their current state.
		 * The current state should be reflected in their 'checked' attribute, NOT in their value.
		 * For example, if there's a value 'foo' for a checkbox when checked, and the current model holds 'bar',
		 * (or null) then we need to mark it as unchecked. However, if we are doing with a post/errors then we need
		 * to handle that too.
		 * AFAIK This method is reached during 'populate' (view rendering) only
		 */

		/**
		 * AFAIK There are two types of name to a field: 'name' and 'fieldName'.
		 * The fieldName is what will appear on the html, whereas the name is the model name for the field.
		 */
		$name = $field->getName();
		$value = $field->getValue(); //This should be the value it has if it is checked, and should be set in the formlet.
		$default = $field->getDefault();

		$checked = $this->getCheckboxCheckedState($name, $value, $default);

		if ($checked) {
			$field->setAttribute('checked');
		}

		return $field;
	}

	/**
	 * Get the check state for a checkbox input.
	 * TODO PD/BG need to review and clean this together.
	 * The usage of checkboxes is varied and complex - sometimes the value is relevant.
	 * whereas at other times the value is not relevant, but the existence is.
	 * Also data is attached in different ways to each checkbox / model.
	 * We probably need to determine/decide upon the LaravelRS 'way' for checkboxes,
	 * bearing in mind that subscribers, subscribe types, and model-holding checkboxes
	 * all have slightly different needs...
	 *
	 * Maybe we need to implement different Checkbox fields, then move the state Checker to the field itself?
	 *
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  $default -- no longer used for some reason.
	 * @return bool
	 */
	protected function getCheckboxCheckedState($name, $value, $default) {
		//the name is the field's model name, not input-name-attribute.
		//This avoids the stuff below, probably not such a good idea.
		if (isset($this->subscriber) && $name == $this->subscriber) {
			return !is_null($this->getData($name));
		}

		//This is sometimes redundant, but they are useful for some of the jiggery-pokery we will be doing.
		$prefixedName = $this->getFieldPrefix($name);
		$dataName = $this->transformKey($prefixedName);

		//Was this checkbox unset in session?
		//TODO: Find out what this is for.
		$noOldValue = true;
		if (isset($this->session)) {
			$oldInput = $this->session->getOldInput();
			if (count($oldInput) > 0) {
				if (is_null($this->old($name))) {
					return false;
				}
				$old = Arr::get($oldInput, $dataName);
				$noOldValue = is_null($old);
			}
		}

		//so when loading, we need to find the data that matches the checkbox's value.
		//This stuff below will either return the model which has the value, or the value itself.
		if ($dataName == "") {
			$model = $this->model;
		} else {
			if ($this->isMultiple()) {
				$model = data_get($this->model, "pivot.$dataName") ?? data_get($this->model, $dataName) ?? $this->getData($dataName);
			} else {
				$model = data_get($this->model, $dataName);
			}
		}

		$checked = $noOldValue && is_null($model);

		if (is_array($model)) {
			$checked = in_array($value, $model);
		} elseif ($model instanceof Collection) {
			$checked = $model->contains('id', $value);
		} elseif ($model == $value) {
			$checked = true;
		}
		return $checked;
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
			if (@$this->getKey()) {
				return "[" . $this->getKey() . "]";
			}
			$modelKey = @$this->getModel()->getKey();
			if ($modelKey) {
				return "[" . $modelKey . "]";
			}
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