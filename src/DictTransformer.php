<?php

namespace DictTransformer;

use DictTransformer\Exceptions\MissingTransformException;
use DictTransformer\Exceptions\MissingKeyException;
use DictTransformer\Exceptions\InvalidIdException;
use Entity;
use ForeignKey;
use InverseRelationship;

/**
 * @package DictTransformer
 */
class DictTransformer
{

    /**
     * @var array
     */
    private $entities;

    /**
     * @var array
     */
    private $transformedEntities;

    /**
     * @var array
     */
    private $includeRelationships;

    /**
     * @var array
     */
    private $inverseRelationships;

    /**
     * @var EntityMapping
     */
    private $entityMapping;

    /**
     * DictTransformer constructor.
     *
     * @param EntityMapping $entityMapping
     */
    public function __construct(EntityMapping $entityMapping)
    {
        $this->entityMapping = $entityMapping;
    }

    /**
     * @param mixed  $resource
     * @param string $rootKey
     * @param array  $includes
     *
     * @return array
     */
    public function transform($resource, string $rootKey, array $includes = [])
    {
        $this->entities = [];
        $this->includeRelationships = [];
        $this->inverseRelationships = [];
        $this->transformedEntities = [];

        if(!is_array($resource))
        {
            $resource = [$resource];
        }

        $this->entities[$rootKey] = $resource;

        $this->parseIncludeRelationships($rootKey, $includes);
        $this->fetchRelationships();
        $this->transformEntities();
        $this->reconstructInverseRelationships();

        return [
            'result' => $this->getKeys($resource),
            'entities' => $this->transformedEntities
        ];
    }

    /**
     * @param array $resource
     *
     * @return array
     */
    private function getKeys(array $resource)
    {
        $ids = [];
        foreach($resource as $item)
        {
            $ids[] = $item->getId();
        }

        return $ids;
    }

    // produces a list of unique relationships so we can accurately collect the data we need
    /**
     * @param string $rootKey
     * @param array  $includes
     */
    private function parseIncludeRelationships(string $rootKey, array $includes)
    {
        $includeStrings = [];
        foreach($includes as $includeString)
        {
            $includeStrings[] = "$rootKey.$includeString";
        }

        $this->includesToRelationships($includeStrings);
    }

    /**
     * @param array $includeStrings
     */
    private function includesToRelationships(array $includeStrings)
    {
        $nextIncludeStrings = [];

        foreach($includeStrings as $includeString)
        {
            $parsedIncludeString = $this->parseCurrentRelationship($includeString);
            $currentParent = $parsedIncludeString['currentParent'];
            $currentChild = $parsedIncludeString['currentChild'];

            // If we can't parse any more relationships on this include line, just continue
            if(!$currentChild)
            {
                continue;
            }

            // If we've already got this relationship, just don't set it
            if(!isset($this->includeRelationships[$currentParent]) && !isset($this->includeRelationships[$currentParent][$currentChild]))
            {
                $this->includeRelationships[$currentParent][$currentChild] = $currentChild;
            }

            $nextIncludeStrings[] = $parsedIncludeString['rest'];
        }

        if(!empty($nextIncludeStrings))
        {
            $this->includesToRelationships($nextIncludeStrings);
        }
    }

    /**
     *
     */
    private function fetchRelationships()
    {
        foreach($this->includeRelationships as $parent => $children)
        {
            $parentEntity = $this->entityMapping->getEntity($parent);

            $this->fetchChildren($parentEntity, $children);
        }
    }

    /**
     * @param Entity $parentEntity
     * @param array  $children
     */
    private function fetchChildren(Entity $parentEntity, array $children)
    {
        foreach($children as $child)
        {
            $childEntity = $this->entityMapping->getEntity($child);
            $relationship = $parentEntity->findRelationship($childEntity->getKey());

            switch (true) {

                case $relationship instanceof InverseRelationship:
                    $this->fetchInverseRelationship($parentEntity, $childEntity, $relationship);
                    $this->inverseRelationships[$parentEntity->getKey()][] = $relationship;
                    break;

                case $relationship instanceof ForeignKey:
                    $this->fetchForeignKeys($parentEntity, $childEntity, $relationship);
                    break;
            }
        }
    }

    /**
     * @param Entity              $parent
     * @param Entity              $child
     * @param InverseRelationship $relationship
     *
     * @throws \Exception
     */
    private function fetchInverseRelationship(Entity $parent, Entity $child, InverseRelationship $relationship)
    {
        if(!isset($this->entities[$parent->getKey()]))
        {
            throw new \Exception('shits fucked');
        }

        $parentIds = [];
        $parentName = is_null($relationship->getParentName()) ? $parent->getKey() : $relationship->getParentName();

        foreach($this->entities[$parent->getKey()] as $parentEntity)
        {
            $parentIds = $parentEntity->getId();
        }

        $this->entities[$child->getKey()] = array_merge(
            $child->getRepository()->findBy([$parentName => $parentIds]),
            $this->entities[$child->getKey()]
        );
    }

    /**
     * @param Entity     $parent
     * @param Entity     $child
     * @param ForeignKey $relationship
     *
     * @throws \Exception
     */
    private function fetchForeignKeys(Entity $parent, Entity $child, ForeignKey $relationship)
    {
        if(!isset($this->entities[$parent->getKey()]))
        {
            throw new \Exception('shits fucked');
        }

        $relationshipMethod = 'get' . ucfirst($relationship->getChildName());
        $childIds = [];

        foreach($this->entities[$parent->getKey()] as $parentEntity)
        {
            // doctrine's lazy loading means even though we 'get' the relationship, since we only access the ID, it
            // just pulls the ID from the proxy object, instead of loading the relationship
            $childIds[] = $parentEntity->$relationshipMethod->getId();
        }

        $this->entities[$child->getKey()] = array_merge(
            $child->getRepository()->findBy(['id' => $childIds]),
            $this->entities[$child->getKey()]
        );
    }

    /**
     *
     */
    private function transformEntities()
    {
        foreach($this->entities as $entityKey => $entities)
        {
            $entityType = $this->entityMapping->getEntity($entityKey);
            $this->transformEntityType($entityType, $entities);
        }
    }

    /**
     * @param Entity $entityType
     * @param array  $entities
     */
    private function transformEntityType(Entity $entityType, array $entities)
    {
        foreach($entities as $entity)
        {
            $this->transformEntity($entity, $entityType->getTransformer());
        }
    }

    /**
     *
     */
    private function reconstructInverseRelationships()
    {
        foreach($this->inverseRelationships as $parentKey => $inverseRelationships)
        {
            $parent = $this->entityMapping->getEntity($parentKey);

            $this->reconstructInverseRelationshipsForEntity($parent, $inverseRelationships);
        }
    }

    /**
     * @param Entity $parent
     * @param array  $inverseRelationships
     */
    private function reconstructInverseRelationshipsForEntity(Entity $parent, array $inverseRelationships)
    {
        foreach($inverseRelationships as $inverseRelationship)
        {
            $this->reconstructInverseRelationship($parent, $inverseRelationship);
        }
    }

    /**
     * @param Entity              $parent
     * @param InverseRelationship $inverseRelationship
     */
    private function reconstructInverseRelationship(Entity $parent, InverseRelationship $inverseRelationship)
    {
        foreach($this->transformedEntities[$parent->getKey()] as $parentEntity)
        {
            $childIds = $this->findChildrenForInverseRelationship($parent->getKey(), $parentEntity, $inverseRelationship);

            $parentEntity[$inverseRelationship->getTargetEntity()] = $childIds;
        }
    }

    /**
     * @param string              $parentKey
     * @param array               $transformedEntity
     * @param InverseRelationship $inverseRelationship
     *
     * @return array
     */
    private function findChildrenForInverseRelationship(
        string $parentKey,
        array $transformedEntity,
        InverseRelationship $inverseRelationship
    ) {
        $childIds = [];
        $parentName = is_null($inverseRelationship->getParentName()) ? $parentKey : $inverseRelationship->getParentName();
        $parentRelationshipMethod = 'get' . ucfirst($parentName);

        foreach($this->entities[$inverseRelationship->getTargetEntity()] as $entity)
        {
            if($entity->$parentRelationshipMethod->getId == $transformedEntity['id'])
            {
                $childIds[] = $entity->getId();
            }
        }

        return $childIds;
    }

    /**
     * @param $entity
     * @param $transformer
     *
     * @throws InvalidIdException
     * @throws MissingKeyException
     * @throws MissingTransformException
     */
    private function transformEntity($entity, $transformer)
    {
        if (!method_exists($transformer, 'transform')) {
            throw new MissingTransformException;
        }

        if (!$this->hasKeyConstant($transformer)) {
            throw new MissingKeyException;
        }

        $data = $transformer->transform($entity);

        $idField = $this->getIdField($transformer);

        if (!isset($data[$idField])) {
            throw new InvalidIdException;
        }

        $this->transformedEntities[$transformer::KEY][$data[$idField]] = isset($this->transformedEntities[$transformer::KEY][$data[$idField]])
            ? array_merge($this->transformedEntities[$transformer::KEY][$data[$idField]], $data)
            : $data;
    }

    /**
     * @param string $includeString
     *
     * @return string
     */
    private function parseCurrentRelationship(string $includeString)
    {
        $pos1 = strpos($includeString, '.');
        $pos2 = strpos($includeString, '.', $pos1 + 1);

        if($pos2)
        {
            return [
                'currentParent' => substr($includeString, 0, $pos1),
                'currentChild' => substr($includeString, $pos1 + 1, $pos2),
                'rest' => substr($includeString, $pos1 + 1)
            ];
        }

        return false;
    }

    /**
     * @param $transformer
     *
     * @return string
     */
    private function getIdField($transformer): string
    {
        $transformerName = get_class($transformer);

        return $this->hasIdConstant($transformer) ? $transformerName::ID : 'id';
    }

    /**
     * @param $transformer
     *
     * @return bool
     */
    private function hasIdConstant($transformer)
    {
        return $this->hasConstant($transformer, 'ID');
    }

    /**
     * @param $transformer
     *
     * @return bool
     */
    private function hasKeyConstant($transformer)
    {
        return $this->hasConstant($transformer, 'KEY');
    }

    /**
     * @param        $transformer
     * @param string $constant
     *
     * @return bool
     */
    private function hasConstant($transformer, string $constant)
    {
        $transformerName = get_class($transformer);

        return defined("$transformerName::$constant");
    }
}