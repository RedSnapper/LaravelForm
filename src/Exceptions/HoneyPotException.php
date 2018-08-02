<?php

namespace RS\Form\Exceptions;

use Exception;

class HoneyPotException extends \Exception
{
    public function render($request)
    {
        return redirect()->back();
    }

    public function report(){

    }
}