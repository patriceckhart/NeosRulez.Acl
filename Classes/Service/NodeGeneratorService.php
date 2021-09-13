<?php
namespace NeosRulez\Acl\Service;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeGeneratorService {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Service\NodeService
     */
    protected $nodeService;

    /**
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return void
     * @throws InvalidConfigurationException
     */
    public function onAfterNodePublishing($node, $targetWorkspace)
    {
        $nodeType = $node->getNodeType()->getName();
        $this->nodeService->createAclNodes('Neos.Neos:Document');
    }

}
