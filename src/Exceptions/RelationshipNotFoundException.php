<?php

namespace DictTransformer\Exceptions;

use Exception;

class RelationshipNotFoundException extends Exception
{

    public function __construct(string $parent, string $child)
    {
        $message = "Entity \"$parent\" does not have a relationship to \"$child\" in the Entity Mapping provided.";

        parent::__construct($message);
    }
}