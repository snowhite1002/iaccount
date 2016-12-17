<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CI_account_model extends CI_Model {
	
	const TBL = 'ci_account';
	
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 获得所有账户信息
	 * 
	 * @return array
	 */
	public function get_accounts() {
		$query = $this->db->get(self::TBL);
		if($query->num_rows() > 0){
			return $query->result_array();
		}
		return array();
	}
	
	/**
	 * 新建账户
	 * 
	 * @param string $account_name
	 * @param string $balance
	 */
	public function create_account($account_name, $balance) {
		$insert_arr = array(
			'account_name' 	=> $account_name,
			'balance' 		=> $balance,
			'create_time' 	=> time()
		);
		$this->db->insert(self::TBL, $insert_arr);
		
		if($this->db->insert_id() > 0){
			return true;
		}
		
		return false;
	}
}