<?php
namespace NeosRulez\Acl\RoleEnforcement;

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;

class SecurityAuthorizationPrivilegeMethodCacheFrontend extends VariableFrontend
{

    /**
     * @Flow\Inject
     * @var PolicyRegistry
     */
    protected $policyRegistry;

    public function get(string $entryIdentifier)
    {
        $result = parent::get($entryIdentifier);
        if (!$this->policyRegistry) {
            return $result;
        }
        if ($entryIdentifier === 'methodPermission' && $result) {
            return $this->policyRegistry->postProcessMethodPermissionList($result);
        }
        return $result;
    }
}
