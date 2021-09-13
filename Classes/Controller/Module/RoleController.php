<?php
namespace NeosRulez\Acl\Controller\Module;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use NeosRulez\Acl\Domain\Model\Role;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Fusion\View\FusionView;
use Neos\Fusion\Core\Cache\ContentCache;

class RoleController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var ContentCache
     */
    protected $contentCache;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Repository\RoleRepository
     */
    protected $roleRepository;

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
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Service\NodeService
     */
    protected $nodeService;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;


    /**
     * @return void
     */
    public function indexAction():void
    {
        $roles = $this->roleRepository->findAll();
        if(!empty($roles)) {
            foreach ($roles as $role) {
                $role->internalRoleName = 'NeosRulez.Acl:' . $this->roleService->cleanRoleName($role->getName());
                $role->parentRolesArray = $role->getParentRoles() ? explode(',', $role->getParentRoles()) : [];
            }
        }
        $this->view->assign('roles', $roles);
    }

    /**
     * @return void
     */
    public function newAction():void
    {
        $this->view->assign('roles', $this->policyService->getRoles());
        $this->view->assign('nodes', $this->nodeService->getNodes());
    }

    /**
     * @param Role $role
     * @param array $privileges
     * @param array $parentRoles
     * @return void
     */
    public function createAction(Role $role, array $privileges, array $parentRoles):void
    {
        $this->nodeService->createAclNodes();
        $role->setParentRoles($this->roleService->rolesToString($parentRoles));
        $role->setPrivileges($this->privilegeService->privilegesToJson($privileges, $this->nodeService->getDeniedNodes($privileges['show']), $this->nodeService->getDeniedNodes($privileges['edit']), $this->nodeService->getDeniedNodes($privileges['remove'])));
        $this->roleRepository->add($role);
        $this->flushContentCache();
        $this->redirect('index');
    }

    /**
     * @param Role $role
     * @return void
     */
    public function editAction(Role $role):void
    {
        $this->view->assign('nodeTypes', $this->nodeService->getNodeTypes());
        $this->view->assign('roles', $this->policyService->getRoles());
        $this->view->assign('parentRoles', $this->roleService->rolesToArray($role->getParentRoles()));
        $this->view->assign('nodes', $this->nodeService->getNodes());
        $this->view->assign('privileges', $this->privilegeService->privilegesToArray($role->getPrivileges()));
        $this->view->assign('role', $role);
    }

    /**
     * @param Role $role
     * @param array $privileges
     * @param array $parentRoles
     * @return void
     */
    public function updateAction(Role $role, array $privileges, array $parentRoles):void
    {
        $this->nodeService->createAclNodes();
        $role->setParentRoles($this->roleService->rolesToString($parentRoles));
        $role->setPrivileges($this->privilegeService->privilegesToJson($privileges, $this->nodeService->getDeniedNodes($privileges['show']), $this->nodeService->getDeniedNodes($privileges['edit']), $this->nodeService->getDeniedNodes($privileges['remove'])));
        $this->roleRepository->update($role);
        $this->flushContentCache();
        $this->redirect('index');
    }

    /**
     * @param Role $role
     * @return void
     */
    public function deleteAction(Role $role):void
    {
        $this->roleRepository->remove($role);
        $this->persistenceManager->persistAll();
        $this->flushContentCache();
        $this->redirect('index');
    }

    protected function flushContentCache()
    {
        $this->contentCache->flush();
    }

}
