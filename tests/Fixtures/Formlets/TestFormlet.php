<?php

namespace RS\Form\Tests\Fixtures\Formlets;

use RS\Form\Formlet;

/**
 * A formlet whose fields are declared by the test via a closure.
 */
class TestFormlet extends Formlet
{
    protected $closure;

    public function __construct(?\Closure $closure = null)
    {
        $this->closure = $closure;
    }

    public function prepare(): void
    {
        if (!is_null($this->closure)) {
            ($this->closure)($this);
        }
    }

    public function persist()
    {
        return $this->allPostData()->toArray();
    }
}
