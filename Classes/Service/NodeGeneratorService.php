<?php
namespace NeosRulez\Acl\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

/**
 * @Flow\Scope("singleton")
 */
class NodeGeneratorService {

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Acl\Domain\Repository\NodeRepository
     */
    protected $nodeRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return void
     * @throws InvalidConfigurationException
     */
    public function onAfterNodePublishing($node, $targetWorkspace)
    {
        $nodeType = $node->getNodeType()->getName();
        $context = $this->contextFactory->create();
        $siteNode = $context->getCurrentSiteNode();
        $nodes = (new FlowQuery(array($siteNode)))->find('[instanceof Neos.Neos:Document]')->sort('_index', 'ASC')->get();
        $this->nodeRepository->removeAll();
        foreach ($nodes as $node) {
            $aclNode = new \NeosRulez\Acl\Domain\Model\Node();
            $aclNode->setNodeIdentifier($node->getIdentifier());
            $aclNode->setKind('Neos.Neos:Document');
            $this->nodeRepository->add($aclNode);
            $this->persistenceManager->persistAll();
        }
    }

}
