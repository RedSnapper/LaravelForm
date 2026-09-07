<?php

namespace RS\Form\Tests\Fixtures\Models;

/**
 * A minimal stand-in for an Eloquent model: attribute access via __get, nested arrays
 * become nested stubs so dotted attribute paths resolve.
 */
class FormBuilderModelStub
{
    protected $data;

    public $exists = true;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = new self($val);
            }
            $this->data[$key] = $val;
        }
    }

    public function __get($key)
    {
        return $this->data[$key];
    }

    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    public function relation()
    {
        return null;
    }
}
