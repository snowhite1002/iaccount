<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 主控制器
 * 
 * @author Snowhite <bbz520@126.com>
 * @version 1.0
 */
class Main extends MY_Controller {
	
	public function __construct() {
		parent::__construct();
	}
        
	/**
	 * 首页
	 */
	public function index() {
		// 初始化总余额
		$total_balance = 0;
		
		// 获得账户列表
		$account_arr = $this->get_accounts();
		
		// 计算总余额
		if(!empty($account_arr)){
			foreach($account_arr as $account){
				$total_balance += $account['balance'];
			}
		}
		
		// 将数据传递到页面上
		$data['account_arr'] = $account_arr;
		$data['total_balance'] = $total_balance;
		$this->load->view('main.php', $data);
	}
	
	/**
	 * 创建账户
	 */
	public function create_account() {
		$account_name 	= $this->input->post('account_name', true); // 账户名称
		$balance 		= $this->input->post('balance', true); 		// 初始金额
		
		//echo $account_name;die;
		
		$this->load->model('ci_account_model');
		$res = $this->ci_account_model->create_account($account_name, $balance);
		
		if(!$res){
			$this->ajax_return(1);
		}
		$this->ajax_return(0);
	}
	
	
	/**
	 * 获得账户列表
	 * 
	 * @return array
	 */
	private function get_accounts() {
		$this->load->model('ci_account_model');
		$account_arr = $this->ci_account_model->get_accounts();
		
		return $account_arr;
	}
	
	/**
	 * 获得账目明细
	 */
	public function get_journals($offset = '') {
		$account_id = $this->input->post('account_id', true);
		
		// 初始化结果数组
		$re_data = array();
		// 初始化分页变量
		$pagetext = '';
		
		// 载入账目模型
		$this->load->model('ci_journal_model');
		// 载入分页类
		$this->load->library('pagination');	
		
		// 配置分页类参数
		$config['base_url'] = site_url('main/get_journals/');
		$config['total_rows'] = $this->ci_journal_model->get_journal_num($account_id);
		$config['per_page'] = 10;
		$config['uri_segment'] = 3;
		$config['first_link'] = '首页';
		$config['last_link'] = '末页';
		$config['next_link'] = '下一页';
		$config['prev_link'] = '上一页';

		// 初始化分页类
		$this->pagination->initialize($config);
		
		// 生成分页结果
		$pagetext = $this->pagination->create_links();
		
		// 获得账目列表
		$limit = $config['per_page'];
		$journal_arr = $this->ci_journal_model->get_journals($account_id, $limit, $offset);
		
		// 整理账目数据
		if(!empty($journal_arr)){
			$re_data = $this->organize_data($journal_arr);
		}
		
 		$this->ajax_return(0, '', array('journal' => $re_data, 'pagetext' => $pagetext));
	}
	
	/**
	 * 整理账目数据
	 */
	private function organize_data($journal_arr) {
		$this->load->helper('my_publicfun');
		$account_arr = $this->get_accounts();
		$account_arr = array_column($account_arr, 'account_name', 'id');
		$account_id_arr = array_keys($account_arr);
			
		foreach ($journal_arr as $journal){
			$temp = array();
			if(in_array($journal['resource_account_id'], $account_id_arr)){
				$resource_account_name = $account_arr[$journal['resource_account_id']];
			}
		
			$dest_account_name = '';
			if(in_array($journal['destination_account'], $account_id_arr)){
				$dest_account_name = $account_arr[$journal['destination_account']];
			}
		
			switch($journal['op_type']){
				case 1:
					$op_type = '收入';
					break;
				case 2:
					$op_type = '支出';
					break;
				case 3:
					$op_type = '转账';
					break;
				default:
					$op_type = '非法操作';
					break;
			}
		
			$temp['id'] = $journal['id'];
			$temp['resource_account_name'] = $resource_account_name;
			$temp['op_type'] = $op_type;
			$temp['dest_account_name'] = $dest_account_name;
			$temp['amount'] = $journal['amount'];
			$temp['desc'] = $journal['desc'];
			$temp['create_time'] = date("Y-m-d H:i:s", $journal['create_time']);
		
			$re_data[] = $temp;
		}
		
		return $re_data;
	}
	
	/**
	 * 记账
	 */
	public function add_journal(){
		$account_id 			= $this->input->post('account_id', true);
		$op_type 				= $this->input->post('op_type', true);
		$destination_account 	= $this->input->post('destination_account', true);
		$amount 				= $this->input->post('amount', true);
		$desc 					= $this->input->post('desc', true);
		
		$this->load->model('ci_journal_model');
		$res = $this->ci_journal_model->create_journal($account_id, $op_type, $destination_account, $amount, $desc);
		
		if(!$res){
			$this->ajax_return(1);
		}
		$this->ajax_return(0);
	}
}