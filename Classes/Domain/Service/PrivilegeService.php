<?php
namespace NeosRulez\Acl\Domain\Service;

use Neos\Flow\Annotations as Flow;

class PrivilegeService {

    /**
     * @param array $privileges
     * @param array $showDeniedNodes
     * @param array $editDeniedNodes
     * @param array $removeDeniedNodes
     * @return string
     */
    public function privilegesToJson(array $privileges, array $showDeniedNodes, array $editDeniedNodes, array $removeDeniedNodes):string
    {
        $privilegeItems = [];
        $result = '';
        if(!empty($privileges)) {
            foreach ($showDeniedNodes as $showDeniedNode) {
                $privilegeItems['showDenied'][] = $showDeniedNode;
            }
            foreach ($editDeniedNodes as $editDeniedNode) {
                $privilegeItems['editDenied'][] = $editDeniedNode;
            }
            foreach ($removeDeniedNodes as $removeDeniedNode) {
                $privilegeItems['removeDenied'][] = $removeDeniedNode;
            }
            foreach ($privileges as $i => $privilege) {
                foreach ($privilege as $privilegeItem) {
                    if($privilegeItem != '') {
                        $privilegeItems[$i][] = $privilegeItem;
                    }
                }
            }
            $result = json_encode($privilegeItems, JSON_FORCE_OBJECT);
        }
        return $result;
    }

    /**
     * @param string $privileges
     * @return array
     */
    public function privilegesToArray(string $privileges):array
    {
        $privileges = json_decode($privileges, true);
        $result = [];
        if(!empty($privileges)) {
            foreach ($privileges as $privilegeMethod => $privilegeNodes) {
                if(!empty($privilegeNodes)) {
                    foreach ($privilegeNodes as $privilegeNode) {
                        $result[$privilegeMethod][$privilegeNode] = true;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param string $privilege
     * @return array
     */
    public function createPrivilegeTarget(string $privilege):array
    {
        $result = [
            'matcher' => 'isDescendantNodeOf("' . $privilege . '")'
        ];
        return $result;
    }

    /**
     * @param string $internalRoleName
     * @param int $privilegeIterator
     * @param string $mode
     * @param string $permission
     * @return array
     */
    public function createRolePrivilege($internalRoleName, int $privilegeIterator, string $mode, string $permission = 'GRANT'):array
    {
        $result = [
            'privilegeTarget' => $internalRoleName . '.' . $mode . $privilegeIterator,
            'permission' => $permission
        ];
        return $result;
    }

}
