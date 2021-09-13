<?php
namespace NeosRulez\Acl\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;
use Neos\ContentRepository\Domain\Model\NodeInterface;

class NodeService {

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;


    /**
     * @return array
     */
    public function getNodes():array
    {
        $context = $this->contextFactory->create();
        $siteNode = $context->getCurrentSiteNode();
        $nodes = (new FlowQuery(array($siteNode)))->find('[instanceof Neos.Neos:Document]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
        $result = $this->buildNodeTree($nodes, $siteNode);
        return $result;
    }

    /**
     * @param array $grantedNodes
     * @return array
     */
    public function getDeniedNodes(array $grantedNodes):array
    {
        $allowedNodes = [];
        $result = [];
        foreach ($grantedNodes as $grantedNode) {
            if($grantedNode != '') {
                $allowedNodes[$grantedNode] = true;
            }
        }
        $connection = $this->entityManager->getConnection();
        $nodes = $connection->executeQuery('SELECT * FROM neosrulez_acl_domain_model_node WHERE kind="Neos.Neos:Document"')->fetchAll();
        foreach ($nodes as $node) {
            $nodeIdentifier = $node['nodeidentifier'];
            if(!array_key_exists($nodeIdentifier, $allowedNodes)) {
                $result[] = $nodeIdentifier;
            }
        }
        return $result;
    }

    /**
     * @return \Neos\ContentRepository\Domain\Service\Context
     */
    protected function createContext()
    {
        $context = $this->contextFactory->create(array('workspaceName' => 'live'));
        return $context;
    }

    /**
     * @param mixed $nodes
     * @param mixed $siteNode
     * @return array
     */
    public function buildNodeTree($nodes, $siteNode):array
    {
        $result = [];
        $nodeTree = [];
        $nodeTree[$siteNode->getIdentifier()] = $this->createNodeItem($siteNode);
        if(!empty($nodes)) {
            foreach ($nodes as $node) {
                $nodeTree[] = $this->createNodeItem($node, $node->getParent()->getIdentifier());
            }
        }
        if(!empty($nodeTree)) {
            foreach ($nodeTree as $item) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * @param mixed $node
     * @param mixed $parent
     * @return array
     */
    public function createNodeItem($node, string $parent = null):array
    {
        $nodeTypeConfig = $this->getNodeTypeConfigByNodeTypeName($node->getNodeType()->getName());

        $children = $node->getChildNodes('Neos.Neos:Document');
        $childs = [];
        if(!empty($children)) {
            foreach ($children as $child) {
                $childs[] = $this->createNodeItem($child, $child->getParent()->getIdentifier());
            }
        }

        $item = [
            'identifier' => $node->getIdentifier(),
            'title' => $node->hasProperty('title') ? $node->getProperty('title') : false,
            'icon' => $this->getNodeTypeIcon($nodeTypeConfig),
            'parent' => $parent,
            'children' => $childs
        ];
        return $item;
    }

    /**
     * @param string $nodeTypeName
     * @return array
     */
    public function getNodeTypeConfigByNodeTypeName(string $nodeTypeName):array
    {
        $nodeTypes = $this->nodeTypeManager->getNodeTypes();
        $result = [];
        if(!empty($nodeTypes)) {
            foreach ($nodeTypes as $nodeTypeIdentifier => $nodeType) {
                if($nodeTypeIdentifier == $nodeTypeName) {
                    $result = $nodeType->getLocalConfiguration();
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getNodeTypes():array
    {
        $result = [];
        $nodeTypes = $this->nodeTypeManager->getNodeTypes();
        foreach ($nodeTypes as $nodeType) {
            $nodeTypeName = $nodeType->getName();
            $superTypes = array_key_exists('superTypes', $nodeType->getLocalConfiguration()) ? $nodeType->getLocalConfiguration()['superTypes'] : [];
            if (array_key_exists('Neos.Neos:Content', $superTypes)) {
                $pos1 = strpos($nodeTypeName, 'Neos.Neos');
                $pos2 = strpos($nodeTypeName, 'Mixin');
                if ($pos1 === false && $pos2 === false && $nodeTypeName != 'unstructured') {
                    $result[] = [
                        'name' => $nodeTypeName,
                        'icon' => array_key_exists('ui', $nodeType->getLocalConfiguration()) ? ($nodeType->getLocalConfiguration()['ui'] != null ? $this->getNodeTypeIcon($nodeType->getLocalConfiguration()) : []) : [],
                        'label' => array_key_exists('ui', $nodeType->getLocalConfiguration()) ? $nodeType->getLocalConfiguration()['ui'] != null ? array_key_exists('label', $nodeType->getLocalConfiguration()['ui']) ? $nodeType->getLocalConfiguration()['ui']['label'] : false : false : false
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * @param array $nodeTypeConfig
     * @return string
     */
    public function getNodeTypeIcon(array $nodeTypeConfig):string
    {
        $icon = array_key_exists('ui', $nodeTypeConfig) ? (array_key_exists('icon', $nodeTypeConfig['ui']) ? $nodeTypeConfig['ui']['icon'] : '') : '';
        $pos1 = strpos($icon, '-o');
        if ($pos1 === false) {
            $icon = str_replace('icon-', 'fas fa-', $icon);
        } else {
            $icon = str_replace('-o', '', str_replace('icon-', 'far fa-', $icon));
        }
        return $icon;
    }

}
