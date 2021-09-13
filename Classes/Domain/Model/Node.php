<?php
namespace NeosRulez\Acl\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Node
{

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $nodeIdentifier;

    /**
     * @return string
     */
    public function getNodeIdentifier()
    {
        return $this->nodeIdentifier;
    }

    /**
     * @param string $nodeIdentifier
     * @return void
     */
    public function setNodeIdentifier($nodeIdentifier)
    {
        $this->nodeIdentifier = $nodeIdentifier;
    }

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $kind;

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     * @return void
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    /**
     * @var \DateTime
     */
    protected $created;


    public function __construct() {
        $this->created = new \DateTime();
    }

    /**
     * @return string
     */
    public function getCreated() {
        return $this->created;
    }

}
