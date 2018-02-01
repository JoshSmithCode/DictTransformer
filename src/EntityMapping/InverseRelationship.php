<?php

class InverseRelationship implements RelationshipInterface
{

    /**
     * @var string
     */
    private $targetEntity;

    /**
     * @var string|null
     */
    private $parentName;

    public function __construct(string $targetEntity, string $parentName = null)
    {
        $this->targetEntity = $targetEntity;
        $this->parentName = $parentName;
    }

    /**
     * @return string
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    /**
     * @return string|null
     */
    public function getParentName()
    {
        return $this->parentName;
    }
}