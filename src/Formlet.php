<?php

namespace RS\Form;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\MessageBag as IMessageBag;
use Illuminate\Validation\ValidationException;
use RS\Form\Concerns\ManagesForm;
use RS\Form\Fields\AbstractField;
use RS\Form\Fields\Checkbox;
use RS\NView\View;
use Symfony\Component\HttpFoundation\Response;

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

	abstract public function prepareForm(): void;

	public function rules(): array {
		return [];
	}

	/**
	 * Add a piece of data to the view.
	 * BG: seeing a model coming over as the 'value'..
	 *
	 * @param  string|array $key
	 * @param  mixed        $value
	 * @return Formlet
	 */
	public function with($key, $value = null): Formlet {
		if (is_array($key)) {
			$this->data = array_merge($this->data, $key);
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}

	/*
	 * @return mixed
	 */
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
	public function setMultiple(bool $multiple = true): void {
		$this->multiple = $multiple;
	}

	/**
	 * Get the key for the formlet
	 *
	 * @return mixed
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Get the subscriber fields from the request for a given key.
	 * This will handle pivot fields as required. That's why each id has an array attached to it.
	 *
	 * @param string $key
	 * @return Collection
	 */
	public function getSubscriberFields(string $key): Collection {
		$result = $this->fields($key);
		foreach ($this->formlets[$key] as $formlet) {
			$result = $formlet->subscribe($result);
		}
		return new Collection($result);
	}

	public function getFieldFromName(string $key): array {
		return $this->fields($key);
	}

	//called by a subscriber formlet owner.
	protected function subs($data): array {
		$result = [];
		foreach ($data as $datum) {
			$stuff = $datum->subscribe();
			if (!is_null($stuff)) {
				$result[$datum->getKey()] = $stuff;
			}
		}
		return $result;
	}

	//called within a subscriber formlet
	protected function subscribe(): ?array {
		$result = $this->fields($this->key);
		if (!is_null($result)) {
			$subscriberFieldName = $this->subscriber ?? 'subscriber';
			$subscriberField = $this->fields[$subscriberFieldName]; //AbstractField. Probably a checkbox.
			$value = $subscriberField->getValue();
			if (isset($result[$subscriberFieldName]) && $result[$subscriberFieldName] == $value) {
				unset($result[$subscriberFieldName]);
			} else {
				$result = null;
			}
		}
		return $result;
	}

	/**
	 * Add subscribers to this formlet.
	 * We are going to construct an array of subscriber formlets using $class as a template.
	 * BelongsToMany will contain the builder query for our subscribed values.
	 * We will derive the query for the options, by using getRelated from that and inheriting any joins that apply to it.
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
	public function addSubscribers(string $name,
																 string $formletClass,
																 BelongsToMany $builder,
																 $subscribeOptions = null
																): void {

		$subscribedModels  = $builder->get();
		/**
		 * The following $subscribeOptions code is all rather low-level...
		 */
		if(is_null($subscribeOptions)) {
			$related = $builder->getRelated(); //the basic model.
			$name = $related->getTable();
			$rWheres = [];
			$rBindings = [];
			$oWheres = $builder->getBaseQuery()->wheres;
			$oBindings = $builder->getBaseQuery()->bindings['where'];
			for($i=0; $i < count($oWheres); $i++ ) {
				$where = $oWheres[$i];
				if(explode('.',$where['column'])[0] == $name) {
					$rWheres[] = $where;
					$rBindings[] = $oBindings[$i];
				}
			}
			$options = $related->newQuery();
			$options->getQuery()->wheres = $rWheres;
			$options->getQuery()->bindings['where'] = $rBindings;
			$subscribeOptions = $options->get(); //Should return a collection of models.
		}

		foreach ($subscribeOptions as $option) {
			$formlet = app()->make($formletClass);
			$subscribed = $this->getModelByKey( $option->getKey(), $subscribedModels); //Collection
			$this->addSubscriberFormlet($formlet, $name,$option,$subscribed);
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
	 * @param Collection $subscribed
	 */
	protected function addSubscriberFormlet(Formlet $formlet,
																					string $name,
																					Model $option,
																					Collection $subscribed): void {
		$dataName = $formlet->subscriber ?? $name;
		$multiSub = substr($dataName,-2) === "[]"; //it may be that we are looking at a multi-field.
		$key = $option->getKey();
		$formlet->setKey($key);
		$formlet->setModel($option);
		$formlet->with($dataName,$multiSub ? $subscribed : $subscribed->first());
		$formlet->with('option', $option);
		$formlet->with('master', $this->model);
		$formlet->setName($name);
		$formlet->setMultiple();
		$this->formlets[$name][$key] = $formlet;
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
	 * @return Collection
	 */
	protected function getModelByKey(int $key, Collection $models, $keyName = "id"): Collection {
		return $models->where($keyName, $key);
	}

	protected function isValid(): bool {

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

	protected function redirectIfErrors(array $errors): bool {

		if (count($errors)) {
			throw new ValidationException($this->validator, $this->buildFailedValidationResponse(
				$errors
			));
		}

		return true;
	}

	/**
	 * Create the response for when a request fails validation.
	 * @param array $errors
	 * @return Response
	 */
	protected function buildFailedValidationResponse(array $errors): Response {
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
	public function validate(array $request, array $rules, array $messages = [], array $customAttributes = []): array {
		$this->validator = $this->getValidationFactory()->make($request, $rules, $messages, $customAttributes);

		$this->addCustomValidation($this->validator);

		if ($this->validator->fails()) {
			return $this->formatValidationErrors($this->validator);
		}
		return [];
	}

	public function addCustomValidation(Validator $validator): void {
	}

	public function renderWith($modes): View {
		return $this->create($modes)->render();
	}

	public function store(): ?Model {
		if (!$this->prepare()) {
			return null;
		}
		if ($this->isValid()) {
			return $this->persist();
		}
		return null;
	}

	public function persist(): ?Model {
		if (isset($this->model)) {
			$this->model = $this->model->create($this->fields());
		}
		return $this->model;
	}

	public function delete($key): ?Model {
		return $this->model->destroy($key);
	}

	public function edit(): ?Model {
		$commit = true;
		$fieldsToSave = $this->fields();
		if ($commit && isset($this->model)) {
			$this->model->fill($fieldsToSave);
			$this->model->save();
		}
		return @$this->model;
	}

	public function update(): ?Model {
		if (!$this->prepare()) {
			return $this->model;
		}
		if ($this->isValid()) {
			return $this->edit();
		}
		return $this->model;
	}

	/**
	 * @return string
	 */
	public function getName(): ?string {
		return $this->name;
	}

	public function setKey($key): Formlet {
		$this->key = $key;
		if (isset($this->model) && isset($this->key)) {
			$this->model = $this->model->find($this->key);
		}
		return $this;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name): void {
		$this->name = $name;
	}

	/**
	 * Set the model instance on the form builder.
	 *
	 * @param  mixed $model
	 * @return Formlet
	 */
	public function setModel($model): Formlet {
		$this->model = $model;
		return $this;
	}

	/**
	 * Add a field to the formlet
	 * We will name the field if we can.
	 *
	 * @param AbstractField $field
	 */
	public function add(AbstractField $field): void {
		$name = $field->getName();
		if (!is_null($name) && $name !== "") {
			$this->fieldsNamed = true;

			if (strlen($name) > 2 && substr($name, -2) == "[]") {
				if (!isset($this->fields[$name])) {
					$this->fields[$name] = [$field];
				} else {
					$this->fields[$name][] = $field;
				}
			} else {
				$this->fields[$name] = $field;
			}
		} else {
			$this->fieldsNamed = false;
			$this->fields[] = $field;
		}
	}

	/**
	 * Fetch all fields from the form.
	 * BG: I want to change this so that we get stuff for 'this' formlet also, rather than all.
	 *
	 * @param $name string
	 * @return array
	 */
	public function fields(string $name = null): array {
		if ($this->multiple) {
			$fields = $this->request->input($this->name); //
			$fields = @$fields[$name] ?? [];
			return $this->rationalise($fields);
		} else {
			if (is_null($name)) {
				if ($this->name != "") {
					$fields = $this->request->input($this->name) ?? [];
				} else {
					$fields = $this->request->all();
				}
				return $this->rationalise($fields);
			} else {
				if (array_key_exists($name, $this->formlets)) {
					$key = $this->getKey();
					if (is_null($key)) {
						foreach ($this->formlets[$name] as $formlet) {
							$fields[] = $formlet->fields();
						}
					} else {
						foreach ($this->formlets[$name] as $formlet) {
							$fields[$key] = $formlet->fields();
						}
					}
				} else {
					$fields = $this->request->input($name) ?? [];
				}
				return $this->rationalise($fields);
			}
		}
	}

	/**
	 * We are doing this to include Checkboxes/'checkable' which otherwise aren't being updated because they aren't being
	 * posted. Be aware that if your checkbox field is not nullable, you will need to cast it or use a mutator.
	 *
	 * @param $postedFields array
	 * @return array
	 */
	protected function rationalise(array $postedFields): array {
		$result = $postedFields;
		//if($this->multiple) {
		//	$theResult = [];
		//	foreach ($this->fields as $field) {
		//		if (is_a($field, Checkbox::class)) {
		//			$modelName = $field->getName();
		//			foreach($result as $post) {
		//				if (!empty($modelName)) {
		//					$post[$modelName] = $post[$modelName] ?? $field->unChecked();
		//				} else {
		//					$post[$modelName] = $field->unChecked();
		//				}
		//				$theResult[] = $post;
		//			}
		//		}
		//	}
		//	$result = $theResult;
		//} else {
		foreach ($this->fields as $field) {
			if (is_a($field, Checkbox::class)) {
				$modelName = $field->getName();
				if (!empty($modelName) && !isset($result[$modelName])) {
					$result[$modelName] = $field->unChecked();
//					$post[$modelName] = $post[$modelName] ?? $field->unChecked();
				}
			}
		}
//		}
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

	protected function doFieldNames(array $fields): void {
		foreach ($fields as $field) {
			if (is_array($field)) {
				$this->doFieldNames($field);
			} else {
				$name = $field->getName(); // ?? "";
				$prefixedName = $this->getFieldPrefix($name);
				$field->setFieldName($prefixedName);
			}
		}
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
			$this->name = null;
		}

		$this->doFieldNames($this->fields);
		return true;
	}

	protected function prepareFormlets(array $formlets): void {

		foreach ($formlets as $name => $formlet) {
			if (is_array($formlet)) {
				$this->prepareFormlets($formlet);
			} else {
				$formlet->prepare($this);
			}
		}
	}

	public function render(): View {
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

	/**
	 * This has mixed return types and needs to be split.
	 *
	 * @param array $formlets
	 * @return mixed
	 **/
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

	public function renderFormlet(): ViewContract {

		$errors = $this->getErrors(); //		MessageBag

		$data = [
			'fields' => $this->getFieldData($this->fields),
			'model'  => $this->getModel()
		];

		$data = array_merge($data, $this->data);

		return view($this->formletView, $data)->withErrorBag($errors);
	}

	protected function getFieldData(array $fields): array {
		return array_map(function ($field) {
			if (is_array($field)) {
				return $this->getFieldData($field);
			} else { //$field is an AbstractField.
				return $field->getData();
			}
		}, $fields);
	}

	/**
	 * Get the value that should be assigned to the field.
	 *
	 * @param  string $name
	 * @param  mixed $value
	 * @param  mixed $default
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
	 * @return string
	 */
	protected function transformKey($key): string {
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
	 * Always returns a MessageBag.
	 *
	 * @return MessageBag
	 */
	protected function getErrors(): MessageBag {
		$errors = $this->session->get('errors');

		return is_null($errors) ? new IMessageBag : $errors->getBag('default');
	}

	protected function setValues(array $fields): void {
		foreach ($fields as $field) {
			if (is_array($field)) {
				$this->setValues($field);
			} else {
				if (is_null($type = $field->getType())) {
					$this->setFieldValue($field);
				} else {
					$this->$type($field);
				}
			}
		}
	}

	protected function populate(): void {
		$this->transformGuardedAttributes();
		$this->setValues($this->fields);
		$this->populateFormlets($this->formlets);
	}

	protected function populateFormlets(array $formlets = []): void {
		foreach ($formlets as $formlet) {
			if (is_array($formlet)) {
				$this->populateFormlets($formlet);
			} else {
				$formlet->populate();
			}
		}
	}

	protected function transformGuardedAttributes(): void {
		$this->guarded = array_map(function ($item) {
			return $this->getFieldPrefix($item);
		}, $this->guarded);
	}

	protected function checkable(AbstractField $field): AbstractField {

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
		$checked = $this->getCheckboxCheckedState($field,$name);

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
	 * Maybe we need to implement different Checkbox fields, then move the state Checker to the field itself?
	 *
	 * @param  AbstractField $field
	 * @param  string $name
	 * @return bool
	 */
	protected function getCheckboxCheckedState(AbstractField $field, $name): bool {
		//the name is the field's model name, not input-name-attribute.
		//This avoids the stuff below, probably not such a good idea.
		if (isset($this->subscriber) && $name == $this->subscriber) {
			$value = $this->getData($name); //we should rationalise this to always return a Collection.
			$checked = (
					  !is_null($value)
				&& (!is_array($value) || count($value) != 0 )
				&& (!$value instanceof Collection || !$value->isEmpty())
			);
			return $checked;
		}

		//This is sometimes redundant...
		//They get access to the data structure, but NOT the model.
		//Model attribute is via the $name above,  I think.
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
		if ($name != "") {
			$value = null;
			if ($this->isMultiple()) {
				$value = data_get($this->model, "pivot.$name") ?? data_get($this->model, $name) ?? $this->getData($dataName);
			} else {
				$value = data_get($this->model, $name);
			}
			if(isset($value)) {
				return $value != $field->unChecked();
			}
		}
		$checked = $noOldValue && is_null($this->model);
		//
		//if (is_array($model)) {
		//	$checked = in_array($value, $model);
		//} elseif ($model instanceof Collection) {
		//	$checked = $model->contains('id', $value);
		//} elseif ($model == $value) {
		//	$checked = true;
		//}
		return $checked;
	}

	/**
	 * Set url generator
	 *
	 * @param UrlGenerator $url
	 * @return Formlet
	 */
	public function setUrlGenerator(UrlGenerator $url): Formlet {
		$this->url = $url;
		return $this;
	}

	/**
	 * Set request on form.
	 *
	 * @param Request $request
	 * @return Formlet
	 */
	public function setRequest(Request $request): Formlet {
		$this->request = $request;
		return $this;
	}

	/**
	 * Set the session store for formlets
	 *
	 * @param Session $session
	 */
	public function setSessionStore(Session $session): void {
		$this->session = $session;
	}

	/**
	 * Get a validation factory instance.
	 *
	 * @return \Illuminate\Contracts\Validation\Factory
	 */
	protected function getValidationFactory(): Factory {
		return app(Factory::class);
	}

	//nested formlets need nested name.
	public function getModel($name = ""): ?Model {
		if (isset($this->formlets[$name])) {
			return $this->formlets[$name]->getModel();
		} else {
			return $this->model;
		}
	}

	/**
	 * @param string $name
	 * @return Formlet
	 */
	protected function getFormlet($name = "") {
		return @$this->formlets[$name];
	}

	/**
	 * @param string $name
	 * @return Formlet
	 */
	protected function getFormlets($name = "") {
		return @$this->formlets[$name];
	}

	/**
	 * Format the validation errors to be returned.
	 *
	 * @param  \Illuminate\Contracts\Validation\Validator $validator
	 * @return array
	 */
	protected function formatValidationErrors(Validator $validator): array {

		$errors = collect($validator->errors()->getMessages());

		$errors = $errors->keyBy(function ($item, $key) {
			return $this->getFieldPrefix($key);
		});

		return $errors->all();
	}

	protected function getFieldPrefix(string $field=null): string {

		$name = $this->getName();

		if (is_null($name) || $name == "") {
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
	 * @return URL
	 */
	protected function getRedirectUrl(): URL {
		return url(app(UrlGenerator::class)->previous());
	}
}