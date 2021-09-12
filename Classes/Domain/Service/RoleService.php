<?php
namespace NeosRulez\Acl\Domain\Service;

use Neos\Flow\Annotations as Flow;

class RoleService {

    /**
     * @param array $roles
     * @return string
     */
    public function rolesToString(array $roles):string
    {
        $roleItems = [];
        $result = '';
        if(!empty($roles)) {
            foreach ($roles as $i => $roles) {
                if($roles != '') {
                    $roleItems[] = $i;
                }
            }
            $result = implode(',', $roleItems);
        }
        return $result;
    }

    /**
     * @param string $roles
     * @return array
     */
    public function rolesToArray(string $roles):array
    {
        $result = [];
        if($roles) {
            foreach (explode(',', $roles) as $role) {
                $result[$role] = true;
            }
        }
        return $result;
    }

    /**
     * @param string $string
     * @return string
     */
    public function cleanRoleName(string $string):string
    {
        $string = str_replace(' ', '', $string);
        $string = str_replace('-', '', $string);
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        $string = preg_replace('/-+/', '-', $string);
        return ucfirst($string);
    }

}
