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
	 * Fieldname of the field.
	 *
	 * @var string
	 */
	protected $fieldName;

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
     * TODO What does this mean??
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
     * Get form name
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
		return $this;
	}

    /**
     * @return null|string
     */
    public function getFieldName(): ?string {
		return $this->fieldName ?? $this->getName();
	}

	/**
	 * Error name removes array string from the field name
	 * @return string
	 */
	public function getErrorName(): string {
		$fieldName = $this->getFieldName();
		return ends_with($this->getFieldName(),'[]') ? substr($fieldName,0,-2) : $fieldName;
	}

	/**
	 * @param string $fieldName
	 */
	public function setFieldName(string $fieldName) {
		$this->fieldName = $fieldName;
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

	public function data(): array {
		$attributes = $this->attributes();
		$value = $this->getValue();
		$label = $this->getLabel();
		$name = $this->getFieldName();
		$field = $this->getName();
		$view = $this->getView();
		$errorName = $this->getErrorName();

		return compact('attributes', 'value', 'label', 'name', 'view', 'field','errorName');
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