<?php namespace App\Models;

use CodeIgniter\Model;

class StatusModel extends Model {

	protected $table         = 'user_status';
	protected $primaryKey    = 'id';
	protected $beforeInsert  = ['formatData'];
	protected $beforeUpdate  = ['formatData'];
	protected $allowedFields = [
		'status',
		'code',
	];

	protected function formatData($data)
	{
		if (isset($data['data']['status']))
		{
			$data['data']['status'] = strtolower($data['data']['status']);
		}
		return $data;
	}

	function status($statusId)
	{
		return $this->where('code', $statusId)->first();
	}

	function select_where($select, $where)
	{
		return $this->select($select)->where($where)->first();
	}
}
