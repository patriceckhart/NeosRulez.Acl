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

                array_push($role['parentRolesArray'], 'Neos.Neos:LivePublisher', 'Neos.Neos:RestrictedEditor', 'NeosRulez.Acl:AbstractEditor');

                foreach ($role['privilegesArray'] as $i => $privileges) {
                    if($i == 'showDenied') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\Neos\Security\Authorization\Privilege\NodeTreePrivilege'][$role['internalRoleName'] . '.Show' . $privilegeIterator] = $this->privilegeService->createPrivilegeTarget($privilege);
                            $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'Show', 'DENY');
                        }
                    }
                    if($i == 'editDenied') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePrivilege'][$role['internalRoleName'] . '.Edit' . $privilegeIterator] = $this->privilegeService->createPrivilegeTarget($privilege);
                            $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'Edit', 'DENY');
                        }
                    }
                    if($i == 'removeDenied') {
                        foreach ($privileges as $privilegeIterator => $privilege) {
                            $customConfiguration['privilegeTargets']['Neos\ContentRepository\Security\Authorization\Privilege\Node\RemoveNodePrivilege'][$role['internalRoleName'] . '.Remove' . $privilegeIterator] = $this->privilegeService->createPrivilegeTarget($privilege);
                            $rolePrivileges[] = $this->privilegeService->createRolePrivilege($role['internalRoleName'], $privilegeIterator, 'Remove', 'DENY');
                        }
                    }

                    $customConfiguration['roles'][$role['internalRoleName']] = [
                        'description' => $role['description'],
                        'parentRoles' => $role['parentRolesArray'],
                        'privileges' => $rolePrivileges
                    ];
                }

            }
        }

        $this->policyRegistry->registerPolicyAndMergeThemWithOriginal($customConfiguration, $configuration);
    }

}
