<?php

class ForeignKey
{

    /**
     * @var string
     */
    private $targetEntityKey;

    /**
     * @var null|string
     */
    private $idColumn;

    /**
     * @param string      $targetEntityKey
     * @param null|string $idColumn
     */
    public function __construct($targetEntityKey, $idColumn = null)
    {
        $this->targetEntityKey = $targetEntityKey;
        $this->idColumn = $idColumn;
    }
}