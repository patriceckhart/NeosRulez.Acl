<?php
namespace NeosRulez\Acl\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use NeosRulez\Acl\Domain\Model\Role;

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
        $result = [];
        foreach ($grantedNodes as $grantedNodeIdentifier => $grantedNode) {
            if($grantedNode == '') {
                $result[] = $grantedNodeIdentifier;
            }
        }
        return $result;
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

        $icon = array_key_exists('ui', $nodeTypeConfig) ? (array_key_exists('icon', $nodeTypeConfig['ui']) ? $nodeTypeConfig['ui']['icon'] : false) : false;
        $pos1 = strpos($icon, '-o');
        if ($pos1 === false) {
            $icon = str_replace('icon-', 'fas fa-', $icon);
        } else {
            $icon = str_replace('-o', '', str_replace('icon-', 'far fa-', $icon));
        }

        $item = [
            'identifier' => $node->getIdentifier(),
            'title' => $node->hasProperty('title') ? $node->getProperty('title') : false,
            'icon' => $icon,
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
            $pos1 = strpos($nodeTypeName, 'Neos.Neos');
            $pos2 = strpos($nodeTypeName, 'Mixin');
            if ($pos1 === false && $pos2 === false && $nodeTypeName != 'unstructured') {
                $result[] = $nodeType;
            }
        }
        return $result;
    }

}
