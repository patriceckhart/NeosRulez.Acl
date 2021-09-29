<?php
namespace NeosRulez\Acl\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Repository\AssetCollectionRepository;

class AssetService {

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;


    /**
     * @return array
     */
    public function getAssetCollections():array
    {
        $result = [];
        $assetCollections = $this->assetCollectionRepository->findAll();
        if(!empty($assetCollections)) {
            foreach ($assetCollections as $assetCollection) {
                $result[] = [
                    'identifier' => $this->persistenceManager->getIdentifierByObject($assetCollection),
                    'title' => $assetCollection->getTitle()
                ];
            }
        }
        return $result;
    }

    /**
     * @param \NeosRulez\Acl\Domain\Model\Role $role
     * @return array
     */
    public function getAssetCollectionsByRole(\NeosRulez\Acl\Domain\Model\Role $role):array
    {
        $result = [];
        $assetCollections = $this->getAssetCollections();
        $grantedAssetCollections = json_decode($role->getPrivileges(), true);
        if(array_key_exists('assetCollections', $grantedAssetCollections)) {
            if(!empty($grantedAssetCollections['assetCollections'])) {
                foreach ($grantedAssetCollections as $grantedAssetCollection) {
                    if(!empty($assetCollections)) {
                        foreach ($assetCollections as $assetCollection) {
                            if(!empty($grantedAssetCollection)) {
                                foreach ($grantedAssetCollection as $grantedAssetCollectionItem) {
                                    if($assetCollection['identifier'] == $grantedAssetCollectionItem) {
                                        $result[] = $assetCollection;
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param array $grantedAssetCollections
     * @param string $mode
     * @return array
     */
    public function getGrantedOrDeniedAssetCollections(array $grantedAssetCollections, string $mode = 'DENIED'): array
    {
        $connection = $this->entityManager->getConnection();
        $result = [];
        foreach ($grantedAssetCollections as $grantedAssetCollection) {
            $grantedPrivileges[] = $grantedAssetCollection;
        }
        if(!empty($grantedPrivileges)) {
            $granted = [];
            foreach ($grantedPrivileges as $grantedPrivilege) {
                if($grantedPrivilege != '') {
                    $granted[$grantedPrivilege] = true;
                }
            }
            $assetCollections = $connection->executeQuery('SELECT * FROM neos_media_domain_model_assetcollection')->fetchAll();
            if(!empty($assetCollections)) {
                foreach ($assetCollections as $assetCollection) {
                    if($mode == 'DENIED') {
                        if(!array_key_exists($assetCollection['persistence_object_identifier'], $granted)) {
                            $result[] = $assetCollection['persistence_object_identifier'];
                        }
                    } else {
                        if(array_key_exists($assetCollection['persistence_object_identifier'], $granted)) {
                            $result[] = $assetCollection['persistence_object_identifier'];
                        }
                    }
                }
            }
        }
        return $result;
    }

}
