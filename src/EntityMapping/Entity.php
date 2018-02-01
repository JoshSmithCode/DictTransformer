<?php

use DictTransformer\Exceptions\RelationshipNotFoundException;
use Doctrine\ORM\EntityRepository;

class Entity
{

    /**
     * @var array
     */
    private $foreignKeys;

    /**
     * @var array
     */
    private $inverseRelationships;

    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $transformer;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @param string           $key
     * @param mixed            $transformer
     * @param EntityRepository $repository
     */
    public function __construct($key, $transformer, EntityRepository $repository)
    {
        $this->key = $key;
        $this->transformer = $transformer;
        $this->repository = $repository;
    }

    /**
     * @param ForeignKey $foreignKey
     *
     * @return $this
     */
    public function addForeignKey(ForeignKey $foreignKey)
    {
        $this->foreignKeys[$foreignKey->getTargetEntity()] = $foreignKey;

        return $this;
    }

    /**
     * @param InverseRelationship $inverseRelationship
     *
     * @return $this
     */
    public function addInverseRelationship(InverseRelationship $inverseRelationship)
    {
        $this->inverseRelationships[$inverseRelationship->getTargetEntity()] = $inverseRelationship;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getTransformer()
    {
        return $this->transformer;
    }

    /**
     * @return EntityRepository
     */
    public function getRepository(): EntityRepository
    {
        return $this->repository;
    }

    /**
     * @param string $key
     *
     * @return RelationshipInterface
     * @throws RelationshipNotFoundException
     */
    public function findRelationship(string $key)
    {
        if(isset($this->foreignKeys[$key]))
        {
            return $this->foreignKeys[$key];
        }

        if(isset($this->inverseRelationships[$key]))
        {
            return $this->inverseRelationships[$key];
        }

        throw new RelationshipNotFoundException($this->getKey(), $key);
    }

    /**
     * Simple shorthand method to see if an entity has an inverse relationship to a child.
     * Used for reconstructing inverse relationships after transformation
     *
     * @param string $key
     *
     * @return InverseRelationship|null
     */
    public function getInverseRelationship(string $key)
    {
        if(isset($this->inverseRelationships[$key]))
        {
            return $this->inverseRelationships[$key];
        }

        return null;
    }
}