<?php

Class tradingvendorModel Extends baseModel {
	protected $table = "trading_vendor";

	public function getAllVendor($data = null,$join = null) 
    {
        return $this->fetchAll($this->table,$data,$join);
    }

    public function createVendor($data) 
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
            'bank' => $data['bank'],
            'account' => $data['account'],
        	);*/

        return $this->insert($this->table,$data);
    }
    public function updateVendor($data,$where) 
    {    
        if ($this->getVendorByWhere($where)) {
        	/*$data = array(
            'staff_id' => $data['staff_id'],
            'staff_name' => $data['staff_name'],
            'staff_birth' => $data['staff_birth'],
            'staff_gender' => $data['staff_gender'],
            'staff_address' => $data['staff_address'],
            'staff_phone' => $data['staff_phone'],
            'staff_email' => $data['staff_email'],
            'cmnd' => $data['cmnd'],
            'bank' => $data['bank'],
            'account' => $data['account'],
            );*/
	        return $this->update($this->table,$data,$where);
        }
        
    }
    public function deleteVendor($id){
    	if ($this->getVendor($id)) {
    		return $this->delete($this->table,array('trading_vendor_id'=>$id));
    	}
    }
    public function getVendor($id){
        return $this->getByID($this->table,$id);
    }
    public function getVendorByWhere($where){
    	return $this->getByWhere($this->table,$where);
    }
    public function getAllVendorByWhere($id){
        return $this->query('SELECT * FROM trading_vendor WHERE trading_vendor_id != '.$id);
    }
    public function getLastVendor(){
        return $this->getLast($this->table);
    }
    public function queryVendor($sql){
        return $this->query($sql);
    }
}
?>