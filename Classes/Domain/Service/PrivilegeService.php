<?php
namespace NeosRulez\Acl\Domain\Service;

use Neos\Flow\Annotations as Flow;

class PrivilegeService {

    /**
     * @param array $privileges
     * @param array $assetCollections
     * @return string
     */
    public function privilegesToJson(array $privileges, array $assetCollections):string
    {
        $privilegeItems = [];
        $assetCollectionItems = [];
        if(!empty($privileges)) {
            foreach ($privileges as $i => $privilege) {
                foreach ($privilege as $privilegeItem) {
                    if($privilegeItem != '') {
                        $privilegeItems[$i][] = $privilegeItem;
                    }
                }
            }
        }
        $items = $privilegeItems;
        if(!empty($assetCollections)) {
            foreach ($assetCollections as $assetCollection) {
                if($assetCollection != '') {
                    $assetCollectionItems[] = $assetCollection;
                }
            }
            $items['assetCollections'] = $assetCollectionItems;
        }
        $result = json_encode($items, JSON_FORCE_OBJECT);
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
    public function createPrivilegeTargetForNodes(string $privilege):array
    {
        $result = [
            'matcher' => 'isDescendantNodeOf("' . $privilege . '")'
        ];
        return $result;
    }

    /**
     * @param string $privilege
     * @return array
     */
    public function createPrivilegeTargetForAssetsInCollections(string $privilege):array
    {
        $result = [
            'matcher' => 'hasId("' . $privilege . '")'
        ];
        return $result;
    }

    /**
     * @param string $privilege
     * @return array
     */
    public function createPrivilegeTargetForAssets(string $privilege):array
    {
        $result = [
            'matcher' => 'isInCollection("' . $privilege . '")'
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

    /**
     * @param array $grantedAssets
     * @param string $grantedAsset
     * @return bool
     */
    public function isGrantedPrivilege(array $grantedAssets, string $grantedAsset = ''):bool
    {
        $result = false;
        if(!empty($grantedAssets) && $grantedAsset != '') {
            foreach ($grantedAssets as $grantedAssetItem) {
                if($grantedAsset == $grantedAssetItem) {
                    $result = true;
                }
            }
        }
        return $result;
    }

}
