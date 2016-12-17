<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CI_journal_model extends CI_Model {
	
	const TBL = 'ci_journal';
	
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 获得账目明细
	 * @param int $account_id
	 */
	public function get_journals($account_id, $limit, $offset='') {
		$this->db->limit($limit, $offset);
 		$query = $this->db->order_by('id', 'desc')->get_where(self::TBL, array('resource_account_id' => $account_id));

		if($query->num_rows() > 0){
			return $query->result_array();
		}
		
		return array();
	}
	
	public function get_journal_num($account_id) {
		$query = $this->db->order_by('id', 'desc')->get_where(self::TBL, array('resource_account_id' => $account_id));
		
		if($query->num_rows() > 0){
			return $query->num_rows();
		}
		
		return 0;
	}
	
	/**
	 * 创建账目
	 * 
	 * @param number $account_id
	 * @param number $op_type
	 * @param string $destination_account
	 * @param number $amount
	 * @param string $desc
	 */
	public function create_journal($account_id = 0, $op_type = 0, $destination_account = null, $amount = 0, $desc = '') {
		try{
			$this->db->trans_begin();
			
			// 添加账目明细
			$insert_arr = array(
				'resource_account_id' 	=> $account_id,
				'op_type' 				=> $op_type,
				'destination_account' 	=> $destination_account,
				'amount' 				=> $amount,
				'desc' 					=> $desc,
				'create_time' 			=> time()
			);
			$this->db->insert(self::TBL, $insert_arr);
			if($this->db->insert_id() < 1){
				throw new Exception('insert data into ci_journal failed');
			}
			
			// 获得源账户的旧余额
			$old_resource_balance = 0;
			$query = $this->db->select('balance')->get_where('ci_account', array('id' => $account_id));
			if($query->num_rows() > 0){
				$row = $query->row_array();
				$old_resource_balance = $row['balance'];
			}
			
			// 根据操作类型更新账户余额
			switch ($op_type){
				case 1: // 收入
					$new_resource_balance = $old_resource_balance + $amount;
					$this->db->update('ci_account', array('balance' => $new_resource_balance), array('id' => $account_id));
					if($this->db->affected_rows() < 0){
						throw new Exception('update income data into ci_journal failed');
					}
					break;
				case 2: // 支出
					$new_resource_balance = $old_resource_balance - $amount;
					$this->db->update('ci_account', array('balance' => $new_resource_balance), array('id' => $account_id));
					if($this->db->affected_rows() < 0){
						throw new Exception('update expenditure data into ci_journal failed');
					}
					break;
				case 3: // 转账
					$new_resource_balance = $old_resource_balance - $amount;
					$this->db->update('ci_account', array('balance' => $new_resource_balance), array('id' => $account_id));
					if($this->db->affected_rows() < 0){
						throw new Exception('transfer:update expenditure data into ci_journal failed');
					}
					
					$old_destination_balance = 0;
					$query_1 = $this->db->select('balance')->get_where('ci_account', array('id' => $destination_account));
					if($query_1->num_rows() > 0){
						$row_1 = $query_1->row_array();
						$old_destination_balance = $row_1['balance'];
					}
					
					$new_destination_balance = $old_destination_balance + $amount;
					$this->db->update('ci_account', array('balance' => $new_destination_balance), array('id' => $account_id));
					if($this->db->affected_rows() < 0){
						throw new Exception('transfer:update income data into ci_journal failed');
					}
					break;
				default:
					break;
			}
			
			$this->db->trans_commit();
			return true;
		}catch(Exception $e){
			return false;
		}
	}
}