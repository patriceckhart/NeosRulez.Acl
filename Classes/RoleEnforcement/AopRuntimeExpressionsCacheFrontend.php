<?php
namespace NeosRulez\Acl\RoleEnforcement;

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Annotations as Flow;

class AopRuntimeExpressionsCacheFrontend extends StringFrontend
{

    /**
     * @Flow\Inject
     * @var PolicyRegistry
     */
    protected $dynamicPolicyRegistry;

    public function get(string $entryIdentifier)
    {
        $result = parent::get($entryIdentifier);
        if (!$this->dynamicPolicyRegistry) {
            return $result;
        }
        if (empty($result)) {
            $identifierForCatchAll = $this->dynamicPolicyRegistry->getAopRuntimeExpressionEntryIdentifierForCatchAllPrivilegeTarget($entryIdentifier);
            if (!empty($identifierForCatchAll)) {
                return parent::get($identifierForCatchAll);
            }
        }
        return $result;
    }
}
