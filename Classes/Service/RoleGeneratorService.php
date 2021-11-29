<?php
namespace NeosRulez\Acl\Service;

use Neos\Flow\Annotations as Flow;

use Neos\Utility\Arrays;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Security\Authorization\Privilege\NodeTreePrivilege;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeLabelGeneratorInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\AbstractNodePrivilege;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\CreateNodePrivilege;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\CreateNodePrivilegeSubject;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePrivilege;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\NodePrivilegeSubject;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\ReadNodePrivilege;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\RemoveNodePrivilege;
use Neos\Media\Security\Authorization\Privilege\ReadAssetCollectionPrivilege;
use Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege;
use NeosRulez\Acl\RoleEnforcement\PolicyRegistry;

/**
 * @Flow\Scope("singleton")
 */
class RoleGeneratorService {

    /**
     * @Flow\Inject(lazy=false)
     * @var PolicyRegistry
     */
    protected $policyRegistry;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Service\PrivilegeService
     */
    protected $privilegeService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Service\RoleService
     */
    protected $roleService;

    /**
     * @Flow\Inject
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Service\NodeService
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Service\AssetService
     */
    protected $assetService;


    public function onConfigurationLoaded(&$configuration)
    {
        $customConfiguration = [];
        $connection = $this->entityManager->getConnection();
        $rows = $connection->executeQuery('SELECT * FROM neosrulez_acl_domain_model_role')->fetchAll();
        if(!empty($rows)) {
            foreach ($rows as $role) {
                $role['internalRoleName'] = 'NeosRulez.Acl:' . $this->roleService->cleanRoleName($role['name']);
                $role['parentRolesArray'] = $role['parentroles'] ? explode(',', $role['parentroles']) : [];
                $role['privilegesArray'] = json_decode($role['privileges'], true);
                $rolePrivileges = [];
                $adminRolePrivileges = [];

                array_push($role['parentRolesArray'], 'Neos.Neos:LivePublisher', 'Neos.Neos:RestrictedEditor', 'NeosRulez.Acl:AbstractEditor');

                $deniedNodesShow = [];
                if(array_key_exists('show', $role['privilegesArray'])) {
                    $deniedNodesShow = $this->nodeService->getDeniedNodes($role['privilegesArray']['show']);
                }
                $deniedNodesEdit = [];
                if(array_key_exists('edit', $role['privilegesArray'])) {
                    $deniedNodesEdit = $this->nodeService->getDeniedNodes($role['privilegesArray']['edit']);
                }
                $deniedNodesRemove = [];
                if(array_key_exists('remove', $role['privilegesArray'])) {
                    $deniedNodesRemove = $this->nodeService->getDeniedNodes($role['privilegesArray']['remove']);
                }
                $deniedNodeTypes = [];
                if(array_key_exists('editNodeTypes', $role['privilegesArray'])) {
                    $deniedNodeTypes = $role['privilegesArray']['editNodeTypes'];
                }
                $allAssetCollections = [];
                $grantedAssetCollections = [];
                if(array_key_exists('assetCollections', $role['privilegesArray'])) {
                    $deniedAssetCollections = $this->assetService->getGrantedOrDeniedAssetCollections($role['privilegesArray']['assetCollections'], 'DENIED');
                    $grantedAssetCollections = $this->assetService->getGrantedOrDeniedAssetCollections($role['privilegesArray']['assetCollections'], 'GRANTED');
                    $allAssetCollections = array_merge($deniedAssetCollections, $grantedAssetCollections);
                }


                $privilegeItems['showDenied'] = [];
                if(!empty($deniedNodesShow)) {
                    foreach ($deniedNodesShow as $deniedNodeShow) {
                        $privilegeItems['showDenied'][] = $deniedNodeShow;
                    }
                }
                $privilegeItems['editDenied'] = [];
                if(!empty($deniedNodesEdit)) {
                    foreach ($deniedNodesEdit as $deniedNodeEdit) {
                        $privilegeItems['editDenied'][] = $deniedNodeEdit;
                    }
                }
                $privilegeItems['removeDenied'] = [];
                if(!empty($deniedNodesRemove)) {
                    foreach ($deniedNodesRemove as $deniedNodeRemove) {
                        $privilegeItems['removeDenied'][] = $deniedNodeRemove;
                    }
                }
                $privilegeItems['deniedNodeTypes'] = [];
                if(!empty($deniedNodeTypes)) {
                    foreach ($deniedNodeTypes as $deniedNodeType) {
                        $privilegeItems['deniedNodeTypes'][] = $deniedNodeType;
                    }
                }
                $privilegeItems['deniedAssets'] = [];
                if(!empty($deniedAssets)) {
                    foreach ($deniedAssets as $deniedAsset) {
                        $privilegeItems['deniedAssets'][] = $deniedAsset;
                    }
                }
                $privilegeItems['allAssetCollections'] = [];
                if(!empty($allAssetCollections)) {
                    foreach ($allAssetCollections as $allAssetCollection) {
                        $privilegeItems['allAssetCollections'][] = $allAssetCollection;
                    }
                }


                foreach ($privilegeItems as $i => $privileges) {
                    if($i == 'showDenied') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\Neos\Security\Authorization\Privilege\NodeTreePrivilege'][$role['internalRoleName'] . '.Show' . $privilegeIterator] = $this->privilegeService->createPrivilegeTargetForNodes($privilege);
                            $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'Show', 'DENY');
                        }
                    }
                    if($i == 'editDenied') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePrivilege'][$role['internalRoleName'] . '.Edit' . $privilegeIterator] = $this->privilegeService->createPrivilegeTargetForNodes($privilege);
                            $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'Edit', 'DENY');
                        }
                    }
                    if($i == 'removeDenied') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\ContentRepository\Security\Authorization\Privilege\Node\RemoveNodePrivilege'][$role['internalRoleName'] . '.Remove' . $privilegeIterator] = $this->privilegeService->createPrivilegeTargetForNodes($privilege);
                            $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'Remove', 'DENY');
                        }
                    }

                    if($i == 'deniedNodeTypes') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePrivilege'][$role['internalRoleName'] . '.NodeTypeEdit' . $privilegeIterator] = $this->privilegeService->createPrivilegeTargetForNodeTypes($privilege);
                            $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'NodeTypeEdit', 'DENY');
                        }
                    }

                    if($i == 'allAssetCollections') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\Media\Security\Authorization\Privilege\ReadAssetCollectionPrivilege'][$role['internalRoleName'] . '.AllAssetCollections' . $privilegeIterator] = $this->privilegeService->createPrivilegeTargetForAssetsInCollections($privilege);
                            $customConfiguration['privilegeTargets']['Neos\Media\Security\Authorization\Privilege\ReadAssetPrivilege'][$role['internalRoleName'] . '.AllAssets' . $privilegeIterator] = $this->privilegeService->createPrivilegeTargetForAssets($privilege);
                            $adminRolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'AllAssetCollections', 'GRANT');
                            $adminRolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'AllAssets', 'GRANT');
                            if(!$this->privilegeService->isGrantedPrivilege($grantedAssetCollections, $privilege)) {
                                $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'AllAssetCollections', 'DENY');
                                $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'AllAssets', 'DENY');
                            } else {
                                $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'AllAssetCollections', 'GRANT');
                                $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'AllAssets', 'GRANT');
                            }
                        }
                    }


                    $customConfiguration['roles'][$role['internalRoleName']] = [
                        'description' => $role['description'],
                        'parentRoles' => $role['parentRolesArray'],
                        'privileges' => $rolePrivileges
                    ];

                    $customConfiguration['roles']['Neos.Neos:Administrator'] = [
                        'privileges' => array_merge($adminRolePrivileges, $configuration['roles']['Neos.Neos:Administrator']['privileges'])
                    ];

                    $customConfiguration['roles']['Neos.Neos:Editor'] = [
                        'privileges' => array_merge($adminRolePrivileges, $configuration['roles']['Neos.Neos:Editor']['privileges'])
                    ];

                }

            }
        }

        $this->policyRegistry->registerPolicyAndMergeThemWithOriginal($customConfiguration, $configuration);

    }

}
