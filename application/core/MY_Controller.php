<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
	}
	
	protected function ajax_return($code=0, $msg='', $data=array()) {
		exit(json_encode(array('code'=>$code, 'msg'=>$msg, 'data'=>$data)));
	}
}