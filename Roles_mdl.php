<?php if(!defined('BASEPATH')) exit('No direct access allowed');

class Roles_mdl extends CI_Model{

	public function __construct(){
		parent::__construct();
	}

	public function addRole()
	{
		$data = array(
			'roleName' => $this->input->post('role_name'),
			'roleDescription' => $this->input->post('role_description')
			);
		if($this->db->insert('role_data', $data)){
			return TRUE;	
		}
		else{
			return FALSE;	
		}
	}

	public function updateRoleData($role_id, $role_name, $role_description)
	{
		$query = 'UPDATE role_data SET roleName = "'.$role_name.'", roleDescription = "'.$role_description.'" WHERE id = '.$role_id;
		$this->db->query($query);
	}

	public function getAllRoles(){
		return $this->db->get('role_data')->result_array();
	}

	/**/
	public function deleteRole($role_id)
	{
		$data = array('id'=>$role_id);
		// delete role from roles' array
		$this->db->delete('role_data', $data); 

		$data = array('roleID' => $role_id);
		// delete role and the permissions assigned to it
		$this->db->delete('role_perms', $data); 

		// delete roles assigned to users
		$this->db->delete('user_roles', $data); 
	}

	public function getRoleDescription($id)
	{
		$this->db->select('roleDescription'); 
		$this->db->where('id', $id);
		$this->db->from('role_data');
		return $this->db->get()->result_array();
	}

	public function getAllPermissions()
	{
		return $this->db->get('perm_data')->result_array();
	}

	public function getRolesPermissions($role_id)
	{
		$query = 'SELECT role_perms.permID, perm_data.permName FROM perm_data, role_perms WHERE role_perms.permID = perm_data.id AND role_perms.roleID = '. $role_id;
		return $this->db->query($query)->result_array();
	}

	public function addRolePermissions($role_id, $perm_id)
	{
		$data = array(
			'roleID' => $role_id,
			'permID' => $perm_id,
			'addDate' => date("Y-m-d H:i:s")
		);

		if($this->db->insert('role_perms', $data)){
			return true;	
		}
		else{
			return false;	
		}
	}

	public function removeRolePermission($role_id, $perm_id)
	{ 
		$data = array(
			'roleID' => $role_id,
			'permID' => $perm_id
		);

		$query = "DELETE FROM role_perms WHERE roleID = '".$role_id."' AND permID= '".$perm_id."'";

		if($this->db->query($query)){
			return true;	
		}
		else{
			return false;	
		}
	}

	// returns roles that are related to a certain type
	public function getTypeRoles($type_id)
	{
		$this->db->select('role_id');
		$this->db->where('type_id', $type_id);
		return $this->db->get('users_types_roles')->result_array();
	}
} 
