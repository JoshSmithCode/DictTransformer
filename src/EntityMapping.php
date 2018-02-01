<?php

namespace DictTransformer;

use DictTransformer\Exceptions\DuplicateEntityKeyException;
use DictTransformer\Exceptions\EntityKeyNotMappedException;
use Entity;

class EntityMapping
{
    private $mapping;

    /**
     * @param Entity $entity
     *
     * @throws DuplicateEntityKeyException
     */
    public function addEntity(Entity $entity)
    {
        if(isset($this->mapping[$entity->getKey()]))
        {
            throw new DuplicateEntityKeyException($entity);
        }

        $this->mapping[$entity->getKey()] = $entity;
    }

    /**
     * @param string $key
     *
     * @return Entity
     * @throws EntityKeyNotMappedException
     */
    public function getEntity(string $key)
    {
        if(!isset($this->mapping[$key]))
        {
            throw new EntityKeyNotMappedException($key);
        }

        return $this->mapping[$key];
    }
}