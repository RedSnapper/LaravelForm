<?php
declare(strict_types=1);

namespace RS\Form\Fields;

use Illuminate\Contracts\View\View;
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
     * Whether or not to include an ID
     * on the field
     * @var bool
     */
	protected $includeID = true;

    /**
     * Attributes for field
     *
     * @var Collection
     */
    protected $errors;

	/**
	 * Value of field
	 *
	 * @var mixed
	 */
	protected $value;

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
     * Is this a multi field
     * Array of values for this field
     * @var bool
     */
    protected $multiple = false;

    /**
     * Should this field be populated
     * @var bool
     */
    protected $guarded = false;

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
	 * Get value for a field
	 *
	 * @return mixed
	 */
	public function getValue() {
		return is_null($this->value) ? $this->default : $this->value;
	}

    /**
     * Get html value (used for rendering)
     * @return mixed
     */
	public function getHTMLValue(){
	    if($this->isGuarded()){
	        return $this->default;
        }
        return $this->getValue();
    }

	/**
	 * Set value for a field
	 *
	 * @param $value mixed
	 * @return AbstractField
	 */
	public function setValue($value):AbstractField {
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
        $this->setAttributeName();
		return $this;
	}

    /**
     * Set the instance name for a field (used in a formlet)
     * @param string $fieldName
     */
    public function setInstanceName(string $name) {
        $this->instanceName = $name;
        $this->setAttributeName();
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

	public function getErrors():Collection{
	    if(!isset($this->errors)){
	        return collect();
        }
        return $this->errors;
    }

    public function setErrors(Collection $errors):AbstractField{
	    $this->errors = $errors;
	    return $this;
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
     * Set the field to be multi field
     *
     * @param boolean $multiple
     * @return AbstractField
     */
    public function multiple($multiple = true): AbstractField {

        $this->multiple = $multiple;
        $this->setAttributeName();

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


	public function render():View{
	    return view($this->getView(),$this->data());
    }

    public function guarded(bool $value = true){
	    $this->guarded = $value;
	    return $this;
	}

    /**
     * Should this field be populated
     * @return bool
     */
    public function isGuarded():bool{
	    return $this->guarded;
    }

    protected function data(): Collection {

        return collect([
            'attributes'=> $this->attributes()->sortKeys(),
            'value'=> $this->getHTMLValue(),
            'label'=> $this->getLabel(),
            'errors'=> $this->getErrors()
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

    private function setAttributeName()
    {
        $name = $this->getInstanceName();

        if($this->multiple){
            $name = $name . "[]";
        }
        $this->setAttribute("name",$name);
        if($this->includeID){
            $this->setAttribute("id",$name);
        }

    }

}