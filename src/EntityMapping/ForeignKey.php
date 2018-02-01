<?php

class ForeignKey implements RelationshipInterface
{

    /**
     * @var string
     */
    private $targetEntity;

    /**
     * @var null|string
     */
    private $childName;

    /**
     * @param string      $targetEntity
     * @param null|string $childName
     */
    public function __construct($targetEntity, $childName = null)
    {
        $this->targetEntity = $targetEntity;
        $this->childName = is_null($childName) ? $targetEntity : $childName;
    }

    /**
     * @return string
     */
    public function getTargetEntity(): string
    {
        return $this->targetEntity;
    }

    /**
     * @return null|string
     */
    public function getChildName()
    {
        return $this->childName;
    }
}