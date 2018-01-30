<?php

namespace DictTransformer;

use DictTransformer\Exceptions\DuplicateEntityKeyException;
use DictTransformer\Exceptions\EntityKeyNotMappedException;
use Entity;

class EntityMapping
{
    private $mapping;

    public function addEntity(Entity $entity)
    {
        if(isset($this->mapping[$entity->getKey()]))
        {
            throw new DuplicateEntityKeyException($entity);
        }

        $this->mapping[$entity->getKey()] = $entity;
    }

    public function getEntity(string $key)
    {
        if(!isset($this->mapping[$key]))
        {
            throw new EntityKeyNotMappedException($key);
        }

        return $this->mapping[$key];
    }
}