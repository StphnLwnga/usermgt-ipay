<?php namespace App\Models;

use CodeIgniter\Model;

class UserLogsModel extends Model {

	protected $table         = 'user_logs';
	protected $primaryKey    = 'id';
	protected $allowedFields = [
		'userId',
		'logCode',
		'description',
		'userIP',
	];

	protected function select_options()
	{
		$select = 'user_logs.userId,user_logs.logCode as operationID,logs.operation,user_logs.description,user_logs.userIP';
		$this->select($select)->join('logs', 'user_logs.logCode = logs.code');
	}

	function all()
	{
		$this->select_options();
		return $this->findAll();
	}

	function user_log($logId)
	{
		$this->select_options();
		return $this->find($logId);
	}

	function select_where($where)
	{
		$this->select_options();
		return $this->where($where)->findAll();
	}
}
