<?php
declare(strict_types=1);

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
use Illuminate\Support\MessageBag as IMessageBag;
use Illuminate\Validation\ValidationException;
use RS\Form\Concerns\ManagesForm;
use RS\Form\Fields\AbstractField;
use RS\Form\Fields\Checkbox;
use RS\NView\View;
use Symfony\Component\HttpFoundation\Response;

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
    protected $key = null;
    /**
     * If there are multiple of this formlet we need to include
     * the key in the formlet
     *
     * @var bool
     */
    protected $multiple = false;
    /**
     * Used by some subscribers to manage multi-subscription
     *
     * @var string
     */
    protected $pivotColumns = null;
    /**
     * Used by subscribers to manage subscription
     *
     * @var BelongsToMany
     */
    protected $belongs = null;
    /**
     * Extra view data
     *
     * @var array
     */
    protected $data = [];

    abstract public function prepareForm(): void;

    public function rules(): array
    {
        return [];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
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
    public function with($key, $value = null): Formlet
    {
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
    public function getData(string $name)
    {
        return data_get($this->data, $name);
    }

    /**
     * There are multiple of this formlet
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @param bool $multiple
     */
    public function setMultiple(bool $multiple = true): void
    {
        $this->multiple = $multiple;
    }

    /**
     * Get the key for the formlet
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
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
     */
    public function setSessionStore(Session $session): void
    {
        $this->session = $session;
    }

    /**
     * Get a validation factory instance.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidationFactory(): Factory
    {
        return app(Factory::class);
    }

    //nested formlets need nested name.
    public function getModel($name = ""): ?Model
    {
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
    protected function getFormlet($name = "")
    {
        return @$this->formlets[$name];
    }

    /**
     * @param string $name
     * @return Formlet
     */
    protected function getFormlets($name = "")
    {
        return @$this->formlets[$name];
    }

    /**
     * Fetch all fields from the form.
     * BG: I want to change this so that we get stuff for 'this' formlet also, rather than all.
     *
     * @param $name string
     * @return array
     */
    public function fields(string $name = null): array
    {
        if ($this->multiple) {
            $fields = $this->request->input($this->name);
            if (is_null($name)) {
                $stuffs = @$fields[$this->getKey()] ?? [];
            } else {
                if (substr($name, -2) == "[]") { //multi-field also.
                    $key = substr($name, 0, -2);
                    $stuffs = @$fields[$key][$this->getKey()] ?? [];
                } else {

                    if (isset($this->subscriber)) {
                        $stuffs[$name] = @$fields[$this->getKey()][$this->subscriber] ?? [];
                    } else {
                        $stuffs = @$fields[$this->getKey()][$name] ?? [];
                    }
                }
            }
            return $this->rationalise($stuffs);
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
     * Get the subscriber fields from the request for a given key.
     * This will handle pivot fields as required. That's why each id has an array attached to it.
     *
     * @param string $key
     * @return Collection
     */
    public function getSubscriberFields(string $key): Collection
    {
        $result = $this->fields($key);
        foreach ($this->formlets[$key] as $formlet) {
            $fields = $formlet->subscribe($result);
            if (!is_null($fields)) {
                $result[] = $fields;
            }
        }
        return new Collection($result);
    }

    public function getFieldFromName(string $key): array
    {
        return $this->fields($key);
    }

    //called by a subscriber formlet owner.

    /**
     * @param $data
     * @return array
     */
    protected function subs($data): array
    {
        $result = [];
        $fBase = null; //all formlets should be the same type here..
        $multiSub = false;

        //need to setParent of BelongsToMany..

        /** @var Formlet $formlet */
        foreach ($data as $key => $formlet) {
            $this->setFormletParent($formlet, $this->model); //so we can use for persist..
            if (is_null($fBase)) {
                $fBase = $formlet;
                $subscriberField = $formlet->subscriber ?? 'subscriber';
                $multiSub = is_array($formlet->fields[$subscriberField]);
            }
            if ($multiSub) { //not just a many-many, but eg a many-many-many.
                /**
                 * The following fails.. because the key '3' is duplicated.
                 * array(
                 * 3 => array( 'pivot_field' => 6 ),
                 * 3 => array( 'pivot_field' => 7 ),
                 * 4 => array( 'pivot_field' => 2 ),
                 * );
                 */
                //so. this is userteamroles..
                //we have user/team we are picking up roles (in stuff).
                //team = key, user=context..
                //subscribe --> X=>B (in X-B)

                $stuff = $formlet->subscribe();
                $pivot = $formlet->pivotColumns[0]; // if we need multiple values in the stuff, we will need to revisit this.
                $formlet->belongs->detach($key);  // delete all.

                foreach ($stuff as $bit) { //syncWithoutDetaching?
                    $formlet->belongs->attach([$key => [$pivot => $bit]]); //add without detach.
                }
                $result = [];
            } else {
                $stuff = $formlet->subscribe();
                if (!is_null($stuff)) {
                    if (count($stuff) === 0) {
                        $result[] = $key;
                    } else {
                        $result[$key] = $stuff;
                    }
                }
            }
        }
        if (!$multiSub && !is_null($fBase)) {
            $fBase->belongs->sync($result);
        }
        return $result;
    }

    /**
     * Need to think carefully about what this does.
     * Essentially, the purpose of this function is to decide, based on the value of the subscriber, if
     * we are to sync the entire model or if we are going to discard it.
     * To date, the former is done by returning the data stored in the result for this record, while
     * the latter is done by returning null - representing that the record is to be discarded.
     * We don't really want to actually make the change yet, as we are composing the data transfer here.
     * TODO: Some way of indicating the value of the subscriber instead of making the decision here.
     * TODO: This could possibly involve refactoring out the entire method.
     *
     * @return array|null
     */
    protected function subscribe(): ?array
    {
        $subscriberFieldName = $this->subscriber ?? 'subscriber';
        /** @var AbstractField|array $subscriberField */
        $subscriberField = $this->fields[$subscriberFieldName]; //AbstractField. Probably a checkbox.
        if (is_array($subscriberField)) {
            $result = [];
            /** @var AbstractField $subscriberFieldItem */
            foreach ($subscriberField as $subscriberFieldItem) {
                $multiple = $subscriberFieldItem->getAttribute("multiple") ?? false;
                if (!$multiple) {
                    throw new \Exception('Found unusual single in a multiple.');
                }
                $fieldName = $subscriberFieldName; //$subscriberFieldItem->getFieldName();
                $value = $this->fields($fieldName); //currently NOT working for multiples..
                $result = array_merge($result, $value); //really not sure about this...
            }
        } else {
            $result = $this->fields(); //We want all the fields for this subscription.
            $multiple = $subscriberField->getAttribute("multiple") ?? false;
            if ($multiple) {
                throw new \Exception('Found unusual multiple.');
            }
            if (!isset($result[$subscriberFieldName])) {
                return null;
            }
            $value = $subscriberField->castToType($result[$subscriberFieldName]);
            if ($subscriberField->isCheckable()) {
                if (($value === $subscriberField->getValue())) { //so this is the value if it is set..
                    if (!in_array($subscriberFieldName, $this->pivotColumns)) {
                        unset($result[$subscriberFieldName]); //don't want to store subscriber.
                    }
                } else {
                    $result = null;
                }
            } else {
                if ($value !== $subscriberField->unChecked()) {
                    if (!in_array($subscriberFieldName, $this->pivotColumns)) {
                        unset($result[$subscriberFieldName]); //don't want to store subscriber.
                    }
                } else {
                    $result = null;  //do not store.
                }
            }
        }
        return $result;
    }

    /**
     * Add subscribers to this formlet.
     * We are going to construct an array of subscriber formlets using $class as a template.
     * BelongsToMany will contain the builder query for our subscribed values.
     * We will derive the query for the options, by using getRelated from that and inheriting any joins that apply to
     * it.
     *
     * @param string                $name
     *   name is an identifier used to compose the data / fieldnames.
     *   e.g. 'activities'. (from http://localhost/role/3/edit)
     * @param string                $formletClass
     *   class is the subscriber formlet to be used, eg App\Http\Formlets\RoleActivityFormlet::class
     * @param BelongsToMany         $belongs
     *   belongs is a BelongsToMany from the base model, eg.
     *   $this->model->activities() (where the model is eg /App/Models/Role)
     * @param Collection|array|null $subscribeOptions
     */
    public function addSubscribers(
      string $name,
      string $formletClass,
      BelongsToMany $belongs,
      $subscribeOptions = null
    ): void {
        $subscribedModels = $belongs->get();
        /**
         * The following $subscribeOptions code is all rather low-level...
         */
        if (is_null($subscribeOptions)) {
            $related = $belongs->getRelated(); //the basic model.
            $name = $related->getTable();
            $rWheres = [];
            $rBindings = [];
            $oWheres = $belongs->getBaseQuery()->wheres;
            $oBindings = $belongs->getBaseQuery()->bindings['where'];
            for ($i = 0; $i < count($oWheres); $i++) {
                $where = $oWheres[$i];
                if (explode('.', $where['column'])[0] == $name) {
                    $rWheres[] = $where;
                    $rBindings[] = $oBindings[$i];
                }
            }
            $oOrders = $belongs->getBaseQuery()->orders;
            $options = $related->newQuery();
            $options->getQuery()->orders = $oOrders;
            $options->getQuery()->wheres = $rWheres;
            $options->getQuery()->bindings['where'] = $rBindings;
            $subscribeOptions = $options->get(); //Should return a collection of models.
        }

        foreach ($subscribeOptions as $option) {
            /** @var Formlet $formlet */
            $formlet = app()->make($formletClass);
            $formlet->belongs = $belongs;
            $formlet->setPivotColumns();
            $subscribed = $this->getModelByKey($option->getKey(), $subscribedModels); //Collection
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
     * @param Collection $subscribed
     */
    protected function addSubscriberFormlet(
      Formlet $formlet,
      string $name,
      Model $option,
      Collection $subscribed
    ): void {
        $dataName = $formlet->subscriber ?? $name;
        $multiSub = substr($dataName, -2) === "[]"; //it may be that we are looking at a multi-field.
        $key = $option->getKey();
        $formlet->setKey($key);
        $formlet->setModel($option);
        $formlet->with("subscriber", $multiSub ? $subscribed : $subscribed->first());
        $formlet->with('master', $this->model);
        $formlet->setName($name);
        $formlet->setMultiple();
        $this->formlets[$name][$key] = $formlet;
    }

    //eg teamRoles (a user owned)- foreign/parent = User, related = Team,
    //pivotColumns are a thing...
    protected function setPivotColumns()
    {
        $reflection = new \ReflectionClass($this->belongs);
        $prop = $reflection->getProperty("pivotColumns"); //also could get foreignKey this way..
        $prop->setAccessible(true);
        $this->pivotColumns = $prop->getValue($this->belongs);
    }

    protected function setFormletParent(Formlet $formlet, Model $parent): void
    {
        $reflection = new \ReflectionClass($formlet->belongs);
        $prop = $reflection->getProperty("parent"); //also could get foreignKey this way..
        $prop->setAccessible(true);
        $prop->setValue($formlet->belongs, $parent);
    }

    /**
     * Add multiple formlets to this formlet.
     *
     * @param string $name
     * @param string $formletClass
     * @return Formlet
     */
    public function addFormlets(string $name, string $formletClass): Formlet
    {
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
    public function addFormlet(string $name, string $formletClass): Formlet
    {
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
    protected function getModelByKey(int $key, Collection $models, $keyName = "id"): Collection
    {
        return $models->where($keyName, $key);
    }

    protected function isValid(): bool
    {

        $errors = [];

        if (count($this->formlets) == 0) {
            $errors = $this->validate($this->request->all(), $this->rules(), $this->messages());
        }

        foreach ($this->formlets as $formlet) {

            if (is_array($formlet)) {

                foreach ($formlet as $f) {
                    $request = $this->request->input($f->getName() . "." . $f->getKey()) ?? [];
                    $errors = array_merge($errors, $f->validate($request, $f->rules(), $f->messages()));
                }
            } else {
                $request = $this->request->get($formlet->getName()) ?? [];
                $errors = array_merge($errors, $formlet->validate($request, $formlet->rules(), $formlet->messages()));
            }
        }

        return $this->redirectIfErrors($errors);
    }

    protected function redirectIfErrors(array $errors): bool
    {

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
     * @param array $errors
     * @return Response
     */
    protected function buildFailedValidationResponse(array $errors): Response
    {
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
    public function validate(array $request, array $rules, array $messages = [], array $customAttributes = []): array
    {
        $this->validator = $this->getValidationFactory()->make($request, $rules, $messages, $customAttributes);

        $this->addCustomValidation($this->validator);

        if ($this->validator->fails()) {
            return $this->formatValidationErrors($this->validator);
        }
        return [];
    }

    public function addCustomValidation(Validator $validator): void
    {
    }

    public function renderWith($modes): View
    {
        return $this->create($modes)->render();
    }

    public function store(): ?Model
    {
        if (!$this->prepare()) {
            return null;
        }
        if ($this->isValid()) {
            return $this->persist();
        }
        return null;
    }

    public function persist(): ?Model
    {
        if (isset($this->model)) {
            $this->model = $this->model->create($this->fields());
        }
        return $this->model;
    }

    public function delete($key): ?Model
    {
        return $this->model->destroy($key);
    }

    public function edit(): ?Model
    {
        $commit = true;
        $fieldsToSave = $this->fields();
        if ($commit && isset($this->model)) {
            $this->model->fill($fieldsToSave);
            $this->model->save();
        }
        return @$this->model;
    }

    public function update(): ?Model
    {
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
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setKey($key): Formlet
    {
        $this->key = $key;
        if (isset($this->model) && isset($this->key)) {
            $this->model = $this->model->find($this->key);
        }
        return $this;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Set the model instance on the form builder.
     *
     * @param  mixed $model
     * @return Formlet
     */
    public function setModel($model): Formlet
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Add a field to the formlet
     * We will name the field if we can.
     *
     * @param AbstractField $field
     */
    public function add(AbstractField $field): void
    {
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
     * We are doing this to include Checkboxes/'checkable' which otherwise aren't being updated because they aren't
     * being posted. Be aware that if your checkbox field is not nullable, you will need to cast it or use a mutator.
     *
     * @param $postedFields array
     * @return array
     */
    protected function rationalise(array $postedFields): array
    {
        $result = $postedFields;
        foreach ($this->fields as $field) {
            if (is_a($field, Checkbox::class)) {
                $modelName = $field->getName();
                if (!empty($modelName) && !isset($result[$modelName])) {
                    $result[$modelName] = $field->unChecked();
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
    protected function doFunction(): bool
    {
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

    protected function doFieldNames(array $fields): void
    {
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

    protected function prepare(Formlet $parent = null): bool
    {
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

    protected function prepareFormlets(array $formlets): void
    {

        foreach ($formlets as $name => $formlet) {
            if (is_array($formlet)) {
                $this->prepareFormlets($formlet);
            } else {
                $formlet->prepare($this);
            }
        }
    }

    public function render(): View
    {
        if (!$this->prepare()) {
            return null;
        }
        $this->populate();

        $data = [
          'form'       => $this->renderFormlets(),
          'attributes' => $this->attributes,
          'hidden'     => $this->getFieldData($this->hidden)
        ];

        $data = array_merge($data, $this->data);

        return view($this->formView, $data);
    }

    /**
     * This has mixed return types and needs to be split.
     *
     * @param array $formlets
     * @return mixed
     **/
    protected function renderFormlets($formlets = [])
    {

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

    public function renderFormlet(): ViewContract
    {

        $errors = $this->getErrors(); //		MessageBag

        $data = [
          'fields' => $this->getFieldData($this->fields),
          'model'  => $this->getModel()
        ];

        $data = array_merge($data, $this->data);

        return view($this->formletView, $data)->withErrorBag($errors);
    }

    protected function getFieldData(array $fields): array
    {
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
     * @param  AbstractField $field
     * @return mixed
     */
    public function getValueAttribute($field)
    {

        $name = $field->getName();
        $value = $field->getValue();
        /** @var bool $isSet */
        $isSet = $field->getValueIsSet();
        $default = $field->getDefault();
        $multiField = substr($name, -2) === "[]";

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
        if (!is_null($value) && $isSet) {
            return $value;
        }

        if (isset($this->subscriber)) {
            if ($multiField) {
                $accessName = substr($name, 0, -2);
                $sub = $this->getData("subscriber.*.pivot.$accessName");
            } else {
                $sub = $this->getData("subscriber.pivot.$name");
            }
            if (!is_null($sub)) {
                return $sub;
            }
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
    public function old($name)
    {
        if (isset($this->session)) {
            return $this->session->getOldInput($this->transformKey($this->getFieldPrefix($name)));
        }
    }

    /**
     * Transform key from array to dot syntax.
     *
     * @param  string $key
     * @return string
     */
    protected function transformKey($key): string
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
    }

    /**
     * Get the model value that should be assigned to the field.
     *
     * @param  string $name
     * @return mixed
     */
    protected function getModelValueAttribute($name)
    {
        $name = $this->transformKey($name);
        if ($name == "") {
            return $this->model;
        }

        if ($this->isMultiple()) {
            return data_get($this->model, "pivot.$name") ?? data_get($this->model, $name);
        }

        return data_get($this->model, $name);
    }

    protected function setFieldValue(AbstractField $field): AbstractField
    {
        $value = $this->getValueAttribute($field);
        $field->setValue($value);
        return $field;
    }

    /**
     * Returns any errors from the session
     * Always returns a MessageBag.
     *
     * @return MessageBag
     */
    protected function getErrors(): MessageBag
    {
        $errors = $this->session->get('errors');

        return is_null($errors) ? new IMessageBag : $errors->getBag('default');
    }

    protected function setValues(array $fields): void
    {
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

    protected function populate(): void
    {
        $this->transformGuardedAttributes();
        $this->setValues($this->fields);
        $this->populateFormlets($this->formlets);
    }

    protected function populateFormlets(array $formlets = []): void
    {
        foreach ($formlets as $formlet) {
            if (is_array($formlet)) {
                $this->populateFormlets($formlet);
            } else {
                $formlet->populate();
            }
        }
    }

    protected function transformGuardedAttributes(): void
    {
        $this->guarded = array_map(function ($item) {
            return $this->getFieldPrefix($item);
        }, $this->guarded);
    }

    protected function checkable(AbstractField $field): AbstractField
    {

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
        $checked = $this->getCheckboxCheckedState($field);

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
     * @param  string $name
     * @param  mixed  $value
     * @param  bool   $checked
     * @return bool
     */
    protected function getCheckboxCheckedState(AbstractField $field): bool
    {

        $name = $field->getName();

        //the name is the field's model name, not input-name-attribute.
        //This avoids the stuff below, probably not such a good idea.
        if (isset($this->subscriber) && $name == $this->subscriber) {
            $value = $this->getData("subscriber"); //we should rationalise this to always return a Collection.
            $checked = (
              !is_null($value)
              && (!is_array($value) || count($value) != 0)
              && (!$value instanceof Collection || !$value->isEmpty())
            );
            return $checked;
        }

        $request = $this->request($name);

        if (isset($this->session) && !$this->oldInputIsEmpty() && is_null($this->old($name)) && !$request) {
            return false;
        }

        if ($this->missingOldAndModel($name) && !$request) {
            return (bool) $field->getDefault();
        }

        $posted = $this->getValueAttribute($field);

        if (is_array($posted)) {
            return in_array($field->getValue(), $posted);
        } elseif ($posted instanceof Collection) {
            return $posted->contains('id', $field->getValue());
        } else {
            return (bool) $posted;
        }
    }

    /**
     * Determine if the old input is empty.
     *
     * @return bool
     */
    public function oldInputIsEmpty()
    {
        return (isset($this->session) && count($this->session->getOldInput()) == 0);
    }

    /**
     * Determine if old input or model input exists for a key.
     *
     * @param  string $name
     * @return bool
     */
    protected function missingOldAndModel($name)
    {
        return (is_null($this->old($name)) && is_null($this->getModelValueAttribute($name)));
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
     * Format the validation errors to be returned.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @return array
     */
    protected function formatValidationErrors(Validator $validator): array
    {

        $errors = collect($validator->errors()->getMessages());

        $errors = $errors->keyBy(function ($item, $key) {
            return $this->getFieldPrefix($key);
        });

        return $errors->all();
    }

    protected function getFieldPrefix(string $field = null): string
    {

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

        return "{$name}{$instance}[$field][$extra";
    }

    protected function getFieldInstance(): string
    {

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
    protected function getRedirectUrl(): string
    {
        return url(app(UrlGenerator::class)->previous());
    }

    /**
     * Get value from current Request
     *
     * @param $name
     * @return array|null|string
     */
    protected function request($name)
    {
        if (!isset($this->request)) {
            return null;
        }
        return $this->request->input($this->transformKey($name));
    }

}