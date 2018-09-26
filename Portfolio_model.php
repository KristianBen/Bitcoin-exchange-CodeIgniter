<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Portfolio_Model extends CI_Model {

    public $table = 'portfolio';

    public function __construct()
    {
        parent::__construct();
    }

    public function findAll($limit = ' ', $select = '*', $where = array()){
        return $this->db
            ->select($select)
            ->limit($limit)
            ->order_by('sort_position','asc')
            ->where($where)
            ->get($this->table)
            ->result();
    }

    public function find($select = '*', $where = array()){
        return $this->db
            ->select($select)
            ->where($where)
            ->get($this->table)
            ->row();
    }

    public function insert($data = array()){
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data = array()){
        $this->db
            ->where('id', $id)
            ->update($this->table, $data);
    }

    public function updateWhere($where, $data = array()){
        $this->db
            ->where($where)
            ->update($this->table, $data);
    }

    public function delete($where = array()){
        return $this->db
            ->where($where)
            ->delete($this->table);
    }

    public function findAllForAdmin($where=array()) {
        return $this->db
            ->select('p.id,p.title,c.name,p.url')
            ->join('category c','p.category_id=c.id')
            ->order_by('p.sort_position','asc')
            ->where($where)
            ->get($this->table.' p')
            ->result();
    }

     public function get_count_all(){
        return $this->db->count_all($this->table);
    }

    public function find_in_limit($limit, $start){
        return $this->db
                ->select('p.*,c.name, cl.name as client')
                ->join('category c','p.category_id=c.id')
                ->join('clients cl', 'p.client_id=cl.id', 'left')
                ->limit($start, $limit)
                ->order_by('p.sort_position','asc')
                ->get($this->table.' p')
                ->result();
    }

    public function update_position($data){
        foreach($data as $id=>$pos){
            $this->db
                    ->where('id',$id)
                    ->update($this->table, array('sort_position'=>$pos));
        }

    }

}