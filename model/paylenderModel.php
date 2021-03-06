<?php

Class paylenderModel Extends baseModel {
	protected $table = "pay_lender";

	public function getAllLender($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createLender($data) 
    {    
        /*$data = array(
        	'staff_id' => $data['staff_id'],
        	'staff_name' => $data['staff_name'],
        	'staff_birth' => $data['staff_birth'],
        	'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Lender' => $data['Lender'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateLender($data,$where) 
    {    
        if ($this->getLenderByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'Lender' => $data['Lender'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteLender($id){
    	if ($this->getLender($id)) {
    		return $this->delete($this->table,array('pay_lender_id'=>$id));
    	}
    }
    public function getLender($id){
        return $this->getByID($this->table,$id);
    }
    public function getLenderByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllLenderByWhere($id){
        return $this->query('SELECT * FROM pay_lender WHERE pay_lender_id != '.$id);
    }
    public function getLastLender(){
        return $this->getLast($this->table);
    }
    public function queryLender($sql){
        return $this->query($sql);
    }
}
?>