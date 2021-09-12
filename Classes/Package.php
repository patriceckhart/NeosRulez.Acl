<?php
namespace NeosRulez\Acl;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Core\Bootstrap;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Security\Policy\PolicyService;
use NeosRulez\Acl\Service\RoleGeneratorService;

class Package extends BasePackage {

    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap) {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(PolicyService::class, 'configurationLoaded', RoleGeneratorService::class, 'onConfigurationLoaded');
    }

}
