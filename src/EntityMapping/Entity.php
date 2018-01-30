<?php

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
     * @var
     */
    private $repository;

    /**
     * @param string $key
     * @param        $transformer
     */
    function __construct(string $key, $transformer)
    {
        $this->key = $key;
        $this->transformer = $transformer;
    }

    /**
     * @param ForeignKey $foreignKey
     *
     * @return $this
     */
    public function addForeignKey(ForeignKey $foreignKey)
    {
        $this->foreignKeys[] = $foreignKey;

        return $this;
    }

    /**
     * @param InverseRelationship $inverseRelationship
     *
     * @return $this
     */
    public function addInverseRelationship(InverseRelationship $inverseRelationship)
    {
        $this->inverseRelationships[] = $inverseRelationship;

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
}