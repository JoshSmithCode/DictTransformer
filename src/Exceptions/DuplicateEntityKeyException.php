<?php

namespace DictTransformer\Exceptions;

use Exception;
use Entity;

class DuplicateEntityKeyException extends Exception
{

    public function __construct(Entity $entity)
    {
        $message = "Key: {$entity->getKey()} already exists in mapping. Entity keys must be unique.";

        parent::__construct($message);
    }
}