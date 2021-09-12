<?php
namespace NeosRulez\Acl\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class Role
{

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     * @ORM\Column(unique=true)
     */
    protected $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $description;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @var string
     * @ORM\Column(type="text")
     * @ORM\Column(length=9000)
     */
    protected $privileges;

    /**
     * @return string
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    /**
     * @param string $privileges
     * @return void
     */
    public function setPrivileges($privileges)
    {
        $this->privileges = $privileges;
    }

    /**
     * @var string
     * @ORM\Column(type="text")
     * @ORM\Column(length=9000)
     */
    protected $parentRoles;

    /**
     * @return string
     */
    public function getParentRoles()
    {
        return $this->parentRoles;
    }

    /**
     * @param string $parentRoles
     * @return void
     */
    public function setParentRoles($parentRoles)
    {
        $this->parentRoles = $parentRoles;
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
