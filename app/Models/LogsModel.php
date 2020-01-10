<?php namespace App\Models;

use CodeIgniter\Model;

class LogsModel extends Model {

	protected $table         = 'logs';
	protected $primaryKey    = 'id';
	protected $beforeInsert  = ['formatData'];
	protected $beforeUpdate  = ['formatData'];
	protected $allowedFields = [
		'operation',
		'code',
	];

	protected function formatData($data)
	{
		if (isset($data['data']['operation']))
		{
			$data['data']['operation'] = strtoupper($data['data']['operation']);
		}
		return $data;
	}

	function log($logId)
	{
		return $this->where('code', $logId)->first();
	}

	function select_where($select, $where)
	{
		return $this->select($select)->where($where)->first();
	}
}
