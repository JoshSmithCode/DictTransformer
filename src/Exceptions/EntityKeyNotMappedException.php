<?php

namespace DictTransformer\Exceptions;

use Exception;

class EntityKeyNotMappedException extends Exception
{

    public function __construct(string $key)
    {
        $message = "The key \"$key\" was not found in the EntityMapping provided";

        parent::__construct($message);
    }
}