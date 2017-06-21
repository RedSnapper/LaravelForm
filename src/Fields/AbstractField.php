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
	 * Whether the value of the field has been set.
	 *
	 * @var bool
	 */
	protected $valueIsSet = false;

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
	 * value that means field is not-wanted (unsubscribe)
	 *
	 * @var mixed
	 */
	protected $unchecked;

	const TYPE_NULL 		= 0x0001;
	const TYPE_INT 			= 0x0002;
	const TYPE_FLOAT 		= 0x0004;
	const TYPE_STRING 	= 0x0008;
	const TYPE_BOOL 		= 0x0010;
	const TYPE_ARRAY 		= 0x0020;
	protected $valueType = AbstractField::TYPE_STRING | AbstractField::TYPE_NULL;

	public function setValueType(int $type) : AbstractField {
		$this->valueType = $type;
		return $this;
	}

	public function castToType($value) {
			$type = $this->valueType;
			if((($type & AbstractField::TYPE_NULL) === AbstractField::TYPE_NULL) && is_null($value)) {
				return $value;
			}
			//now remove the null option.
			switch ($this->valueType & ~AbstractField::TYPE_NULL) {
				case AbstractField::TYPE_INT: 		return (int) 		$value;
				case AbstractField::TYPE_FLOAT: 	return (float) 	$value;
				case AbstractField::TYPE_STRING:	return (string) $value;
				case AbstractField::TYPE_BOOL:		return (bool) 	$value;
				case AbstractField::TYPE_ARRAY:		return is_array($value) ? $value : [$value];
				break;
			}
	}
	/**
	 * @return mixed
	 */
	public function unChecked() {
		return $this->castToType($this->unchecked);
	}

	public function setUnChecked($value) : void {
		$this->unchecked = $value;
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
	 * Return the type of field eg. checkable
	 *
	 * @return null|string
	 */
	public function getType() : ?string {
		return $this->type;
	}

	/**
	 * Get value of a field
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->castToType($this->value);
	}

	public function getValueIsSet() : bool {
		return $this->valueIsSet;
	}

	/**
	 * Set value for a field
	 *
	 * @param $value mixed
	 * @return AbstractField
	 */
	public function setValue($value) {
		$this->valueIsSet = true;
		$this->value = $value;
		return $this;
	}

	/**
	 * Get the default value for a field
	 *
	 * @return mixed
	 */
	public function getDefault() {
		return $this->castToType($this->default);
	}

	/**
	 * Set default value for a field
	 * @param $default mixed
	 * @return AbstractField
	 */
	public function setDefault($default): AbstractField  {
		$this->default = $default;
		return $this;
	}


	/**
	 * @return string or null(php 7.1 ?string)
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
	 * @return string
	 */
	public function getFieldName(): string {
		return $this->fieldName ?? $this->getName();
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
	public function setLabel(string $label): AbstractField {
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
	public function setView(string $view): AbstractField {
		$this->view = $view;
		return $this;
	}

	/**
	 * Set the field to be disabled
	 *
	 * @param boolean $disabled
	 * @return AbstractField
	 */
	public function setDisabled($disabled = true): AbstractField {

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
	public function setRequired($required = true): AbstractField {

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
	public function setPlaceholder(string $string): AbstractField {
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

	public function setAttributes(array $attributes): AbstractField {
		$this->attributes->merge($attributes);
		return $this;
	}

	public function getData(): array {
		$attributes = $this->attributes;
		$value = $this->getValue();
		$label = $this->getLabel();
		$name = $this->getFieldName();
		$field = $this->getName();
		$view = $this->getView();

		return compact('attributes', 'value', 'label', 'name', 'view', 'field');
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