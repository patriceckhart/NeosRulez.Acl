<?php
namespace NeosRulez\Acl\RoleEnforcement;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeTarget;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Utility\Arrays;

/**
 * @Flow\Scope("singleton")
 */
final class PolicyRegistry
{

    const ALLOWED_PRIVILEGE_TARGET_TYPES = [
        'Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePrivilege' => 'NeosRulez.Acl:EditAllNodes',
        'Neos\ContentRepository\Security\Authorization\Privilege\Node\CreateNodePrivilege' => 'NeosRulez.Acl:CreateAllNodes',
        'Neos\ContentRepository\Security\Authorization\Privilege\Node\RemoveNodePrivilege' => 'NeosRulez.Acl:RemoveAllNodes',
        'Neos\Neos\Security\Authorization\Privilege\NodeTreePrivilege' => 'NeosRulez.Acl:ReadAllNodes',
        'Neos\Media\Security\Authorization\Privilege\ReadAssetCollectionPrivilege' => 'NeosRulez.Acl:ReadAllAssetCollections',
        'Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege' => 'NeosRulez.Acl:ReadAllAssets'
    ];

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $dynamicPrivilegeTargetsPerType = [];

    /**
     * @var boolean
     */
    protected $privilegeMappingsInitialized = false;

    /**
     * @var array
     */
    protected $catchAllToDynamicPrivilegeMapping;

    /**
     * @var array
     */
    protected $dynamicPrivilegeToCatchAllMapping;

    /**
     * @param array $dynamicPolicy
     * @param array $originalPolicy
     */
    public function registerPolicyAndMergeThemWithOriginal(array $dynamicPolicy, array &$originalPolicy)
    {
        if (isset($dynamicPolicy['privilegeTargets'])) {
            foreach ($dynamicPolicy['privilegeTargets'] as $privilegeTargetType => $privilegeTargetsForType) {
                self::ensurePrivilegeTargetIsInDynamicWhitelist($privilegeTargetType);
                $this->dynamicPrivilegeTargetsPerType[$privilegeTargetType] = $privilegeTargetsForType;
            }
            $this->privilegeMappingsInitialized = false;
        }
        $originalPolicy = Arrays::arrayMergeRecursiveOverrule($originalPolicy, $dynamicPolicy);
    }

    private static function ensurePrivilegeTargetIsInDynamicWhitelist(string $privilegeTargetType)
    {
        if (!isset(self::ALLOWED_PRIVILEGE_TARGET_TYPES[$privilegeTargetType])) {
            throw new \RuntimeException('the privilege target type "' . $privilegeTargetType . '" is not allowed to be registered dynamically.');
        }
    }

    /**
     * @param array $methodPermissions
     * @return mixed
     * @throws \Neos\Flow\Security\Exception
     */
    public function postProcessMethodPermissionList(array $methodPermissions)
    {
        $this->initializeDynamicPrivilegeMapping();

        foreach ($methodPermissions as &$inner) {
            foreach ($this->catchAllToDynamicPrivilegeMapping as $cacheIdentifierForCatchAllPrivilegeTarget => $extraCacheIdentifiers) {
                if (isset($inner[$cacheIdentifierForCatchAllPrivilegeTarget])) {
                    foreach ($extraCacheIdentifiers as $cacheIdentifier) {
                        $inner[$cacheIdentifier] = $inner[$cacheIdentifierForCatchAllPrivilegeTarget];
                    }
                }
            }
        }

        return $methodPermissions;
    }

    public function getAopRuntimeExpressionEntryIdentifierForCatchAllPrivilegeTarget(string $dynamicPrivilegeTargetCacheEntryIdentifier): ?string
    {
        $this->initializeDynamicPrivilegeMapping();
        $cacheIdentifier = str_replace('flow_aop_expression_', '', $dynamicPrivilegeTargetCacheEntryIdentifier);

        if (isset($this->dynamicPrivilegeToCatchAllMapping[$cacheIdentifier])) {
            return 'flow_aop_expression_' . $this->dynamicPrivilegeToCatchAllMapping[$cacheIdentifier];
        }

        return null;
    }

    /**
     * @throws \Neos\Flow\Security\Exception
     */
    private function initializeDynamicPrivilegeMapping(): void
    {
        if ($this->privilegeMappingsInitialized === true) {
            return;
        }

        $dynamicPrivilegeMapping = [];
        $dynamicPrivilegeToCatchAllMapping = [];
        foreach ($this->dynamicPrivilegeTargetsPerType as $privilegeTargetType => $dynamicPrivilegeTargets) {
            $catchAllPrivilegeTargetForType = self::ALLOWED_PRIVILEGE_TARGET_TYPES[$privilegeTargetType];
            $matcherForCatchAllPrivilegeTarget = static::getMatcherForCatchAllPrivilegeTargets($this->objectManager)[$catchAllPrivilegeTargetForType];

            $cacheIdentifierForCatchAllPrivilegeTarget = (new PrivilegeTarget($catchAllPrivilegeTargetForType, $privilegeTargetType, $matcherForCatchAllPrivilegeTarget, []))
                ->createPrivilege(PrivilegeInterface::GRANT, [])
                ->getCacheEntryIdentifier();

            $extraCacheIdentifiers = [];
            foreach ($dynamicPrivilegeTargets as $dynamicPrivilegeTargetIdentifier => $dynamicPrivilegeTargetConfiguration) {
                $cacheIdentifier = (new PrivilegeTarget($dynamicPrivilegeTargetIdentifier, $privilegeTargetType, $dynamicPrivilegeTargetConfiguration['matcher'], []))->createPrivilege(PrivilegeInterface::GRANT, [])->getCacheEntryIdentifier();
                $extraCacheIdentifiers[] = $cacheIdentifier;
                $dynamicPrivilegeToCatchAllMapping[$cacheIdentifier] = $cacheIdentifierForCatchAllPrivilegeTarget;
            }

            $dynamicPrivilegeMapping[$cacheIdentifierForCatchAllPrivilegeTarget] = $extraCacheIdentifiers;
        }

        $this->catchAllToDynamicPrivilegeMapping = $dynamicPrivilegeMapping;
        $this->dynamicPrivilegeToCatchAllMapping = $dynamicPrivilegeToCatchAllMapping;
        $this->privilegeMappingsInitialized = true;
    }


    /**
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    public static function getMatcherForCatchAllPrivilegeTargets($objectManager): array
    {
        $catchAllPrivilegeTargetMatchers = [];

        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $policyConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_POLICY);
        foreach (self::ALLOWED_PRIVILEGE_TARGET_TYPES as $catchAllPrivilegeTargetType => $catchAllPrivilegeTargetName) {
            $catchAllPrivilegeTargetMatchers[$catchAllPrivilegeTargetName] = $policyConfiguration['privilegeTargets'][$catchAllPrivilegeTargetType][$catchAllPrivilegeTargetName]['matcher'];
        }

        return $catchAllPrivilegeTargetMatchers;
    }

}
