<?php
declare(strict_types=1);

namespace RS\Form\Fields;

use Illuminate\Support\Collection;

abstract class AbstractField {

	/**
	 * Name of the field.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Instance name of a field.
     * User in formlets
	 *
	 * @var string
	 */
	protected $instanceName;

	/**
	 * Label value
	 *
	 * @var string|null
	 */
	protected $label;

	/**
	 * View for field
	 *
	 * @var string
	 */
	protected $view;

	/**
	 * Attributes for field
	 *
	 * @var Collection
	 */
	protected $attributes;

	/**
	 * Value of field
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Whether we should populate the field
	 *
	 * @var bool
	 */
	protected $populate = true;

	/**
	 * What type of field this
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Default value for field
	 *
	 * @var mixed
	 */
	protected $default;

    /**
     * The value of the checkbox when checked
     */
    protected $checked;

    /**
     * The value of the checkbox when not checked
     */
    protected $unchecked;

    /**
     * @param string $type
     * @return AbstractField
     */
    public function setType(string $type):AbstractField
    {
        $this->type = $type;
        return $this;
    }

	/**
	 * Return the type of field eg. checkable
	 *
	 * @return null|string
	 */
	public function getType() : ?string {
		return $this->type;
	}

    /**
     * Return if this field is a checkable
     *
     * @return bool
     */
    public function isCheckable() : bool {
        return $this->type === "checkable";
    }

	/**
	 * Get value of a field
	 *
	 * @return mixed
	 */
	public function getValue() {

	    if($this->isCheckable()){
	        return $this->getCheckedValue();
        }

		return $this->value;
	}

	/**
	 * Set value for a field
	 *
	 * @param $value mixed
	 * @return AbstractField
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Get the default value for a field
	 *
	 * @return mixed
	 */
	public function getDefault() {
		return $this->default;
	}

	/**
	 * Set default value for a field
	 * @param $default mixed
	 * @return AbstractField
	 */
	public function default($default): AbstractField  {
		$this->default = $default;
		return $this;
	}

    /**
     * Get the original field name
     * @return null|string
     */
    public function getName(): ?string {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return AbstractField
	 */
	public function setName(string $name = null): AbstractField {
		$this->name = $name;
		$this->setAttribute("name",$name);
		return $this;
	}

    /**
     * Set the instance name for a field (used in a formlet)
     * @param string $fieldName
     */
    public function setInstanceName(string $name) {
        $this->instanceName = $name;
        $this->setAttribute("name",$name);
    }

    /**
     * Get the instance name for a field
     * @return null|string
     */
    public function getInstanceName(): ?string {
		return $this->instanceName ?? $this->getName();
	}

	/**
	 * Error name removes array string from the field name
	 * @return string
	 */
	public function getErrorName(): ?string {
		$instanceName = $this->getInstanceName();

		if(is_null($instanceName)){
		    return null;
        }

		return ends_with($this->getInstanceName(),'[]') ? substr($instanceName,0,-2) : $instanceName;
	}


	/**
	 * @return string|null
	 */
	public function getLabel(): ?string {
		return $this->label;
	}

	/**
	 * @param string $label
	 * @return AbstractField
	 */
	public function label(string $label): AbstractField {
		$this->label = $label;
		return $this;
	}

    public function getUnCheckedValue(){
        return $this->unchecked;
    }

    public function getCheckedValue(){
        return $this->checked;
    }

	/**
	 * @return string|null
	 */
	public function getView(): ?string {
		return $this->view;
	}

	/**
	 * @param string $view
	 * @return AbstractField
	 */
	public function view(string $view): AbstractField {
		$this->view = $view;
		return $this;
	}

	/**
	 * Set the field to be disabled
	 *
	 * @param boolean $disabled
	 * @return AbstractField
	 */
	public function disabled($disabled = true): AbstractField {

		$disabled ? $this->setAttribute('disabled')
		  : $this->removeAttribute("disabled");

		return $this;
	}

	/**
	 * Set the field to be disabled
	 *
	 * @param boolean $required
	 * @return AbstractField
	 */
	public function required($required = true): AbstractField {

		$required ? $this->setAttribute('required')
		  : $this->removeAttribute("required");

		return $this;
	}

	/**
	 * Set placeholder
	 *
	 * @param string $string
	 * @return AbstractField
	 */
	public function placeholder(string $string): AbstractField {
		$this->setAttribute('placeholder', $string);
		return $this;
	}

	public function getAttribute(string $attribute) {
		return $this->attributes->get($attribute);
	}

	public function setAttribute(string $attribute, $value = null): AbstractField {
		$this->attributes->put($attribute, $value ?? $attribute);
		return $this;
	}

    public function attributes(): Collection {
        return $this->attributes;
    }

	public function setAttributes(array $attributes): AbstractField {
		$this->attributes = $this->attributes->merge($attributes);
		return $this;
	}

	public function data(): Collection {

	    return collect([
	      'attributes'=> $this->attributes(),
          'value'=> $this->getValue(),
          'label'=> $this->getLabel(),
          'errorName'=> $this->getErrorName()
        ]);
	}

	protected function removeAttribute($key): AbstractField {
		$this->attributes->forget($key);
		return $this;
	}

	function __call($name, $arguments): AbstractField {
		$value = count($arguments) == 0 ? $name : $arguments[0];
		$this->setAttribute(mb_strtolower($name), $value);
		return $this;
	}

}