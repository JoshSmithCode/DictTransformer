<?php

class InverseRelationship
{

    /**
     * @var string
     */
    private $targetEntity;

    /**
     * @param string $targetEntity
     */
    public function __construct($targetEntity)
    {
        $this->targetEntity = $targetEntity;
    }

}