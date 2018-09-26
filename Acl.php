<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Acl
{
    var $perms = array(); // Array: Stores the permissions for the user
    var $userID; // Integer : Stores the ID of the current user
    var $userRoles = array(); // Array: Stores the roles of the current user
    var $ci;

    function __construct()
    {
        $this->ci = & get_instance();
    }

    function buildACL($config = array())
    {
        $this->userID = floatval($config['userID']);
        $this->userRoles = $this->getUserRoles();

        // first, get the rules for the user's role

        $this->perms = array_merge($this->perms, $this->getRolePermissions($this->userRoles));


    }

    function getPermissionKeyFromID($permID)
    {
        $this->ci->db->select('permKey');
        $this->ci->db->where('id', floatval($permID));
        $sql = $this->ci->db->get('perm_data', 1);
        $data= $sql->result();
        return $data[0]->permKey;
    }

    function getPermissionNameFromID($permID)
    {
        $this->ci->db->select('permName');
        $this->ci->db->where('id', floatVal($permID));
        $sql = $this->ci->db->get('perm_data', 1);
        $data= $sql->result();
        return $data[0]->permName;
    }

    function getUserRoles()
    {
        $this->ci->db->where(array('id'=>floatval($this->userID)));
        $sql = $this->ci->db->get('users');
        $data= $sql->result();

        $this->ci->db->where(array('type_id'=>floatval($data[0]->user_type)));
        $sql = $this->ci->db->get('users_types_roles');
        $data= $sql->result();

        $resp= array();
        foreach ( $data as $row ) {
            $resp[] = $row->role_id;
        }

        return $resp;
    }

    function getRoleNameFromID($roleID)
    {
       $this->ci->db->select('roleName');
        $this->ci->db->where('id', floatval($roleID), 1);
        $sql = $this->ci->db->get('role_data');
        $data= $sql->result();
        return $data[0]->roleName;
    }

    function getAllRoles($format = 'ids')
    {
        $format = strtolower($format);
        $this->ci->db->order_by('roleName', 'asc');
        $sql    = $this->ci->db->get('role_data');
        $data   = $sql->result();

        $resp   = array();
        foreach ( $data as $row ) {
            if ($format == 'full') {
                $resp[] = array("id"  => $row->id,"name"=>$row->roleName);
            }
            else {
                $resp[] = $row->id;
            }
        }

        return $resp;
    }

    function getAllPermissions($format = 'ids')
    {
        $format = strtolower($format);
        $this->ci->db->order_by('permKey', 'asc');
        $sql    = $this->ci->db->get('perm_data');
        $data   = $sql->result();

        $resp   = array();
        foreach ( $data as $row ) {
            if ($format == 'full') {
                $resp[$row->permKey] = array('id'  => $row->id,
                    'name'=> $row->permName,
                    'key' => $row->permKey);
            }
            else {
                $resp[] = $row->id;
            }
        }
        return $resp;
    }

    function getRolePermissions($role)
    {
        if (empty($role)) {
            return false;
        }
        if (is_array($role)) {
          
            $this->ci->db->where_in('roleID', $role);
        }
        else {
  

            $this->ci->db->where(array('roleID'=> floatval($role)));
        }

        $this->ci->db->order_by('id', 'asc');
        $sql   = $this->ci->db->get('role_perms'); //$this->db->select($roleSQL);
        $data  = $sql->result();
        $perms = array();

        foreach ( $data as $row ) {
            $pK = strtolower($this->getPermKeyFromID($row->permID));
            if ($pK == '') {
                continue;
            }
            if ($row->value === '1') {
                $hP = true;
            }
            else {
                $hP = false;
            }

            $perms[$pK] = array('perm'      => $pK,
                'inheritted'=> true,
                'value'     => $hP,
                'name'      => $this->getPermNameFromID($row->permID),
                'id'        => $row->permID);
        }
        return $perms;
    }

    function getUserPermissions($userID)
    {
        
        $this->ci->db->where('userID', floatval($userID));
        $this->ci->db->order_by('addDate', 'asc');
        $sql   = $this->ci->db->get('user_perms');
        $data  = $sql->result();

        $perms = array();
        foreach ($data as $row) {
            $pK = strtolower($this->getPermKeyFromID($row->permID));
            if ($pK == '') {
                continue;
            }
            if ($row->value == '1') {
                $hP = true;
            }
            else {
                $hP = false;
            }

            $perms[$pK] = array('perm'      => $pK,
                'inheritted'=> false,
                'value'     => $hP,
                'name'      => $this->getPermNameFromID($row->permID),
                'id'        => $row->permID);
        }
        return $perms;
    }

    function hasRole($roleID)
    {
        foreach ($this->userRoles as $k => $v) {
            if (floatval($v) === floatval($roleID)) {
                return true;
            }
        }
        return false;
    }

    function hasPermission($permKey)
    {
        $permKey = strtolower($permKey);

        if (array_key_exists($permKey, $this->perms)) {
            if ($this->perms[$permKey]['value'] === '1' || $this->perms[$permKey]['value'] === true) {
                return true;
            }
            else {
                return false;
            }
            return true;
        }
        else {
            return false;
        }
    }

  
    function getUserRoleName()
    {
        $roles_names = array();

        $this->ci->db->where(array('userID'=>floatval($this->userID)));
        $this->ci->db->order_by('addDate', 'asc');
        $roles = $this->ci->db->get('user_roles')->result_array();

        foreach ($roles as $role) {
            $roles_names[] = $this->getRoleNameFromId($role['roleID']);
        }

        return $roles_names;
    }

    function getUserRolePermissions()
    {
        $userRoles = $this->getUserRoles();
        $userPermissions = array();

        foreach ($userRoles as $role) {
            $userPermissions[] = $this->getRolePermissions($role);
        }

        return $userPermissions;
    }
}
