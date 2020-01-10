<?php namespace App\Models;

use CodeIgniter\Model;

class PermissionsModel extends Model {

	protected $table         = 'permissions';
	protected $primaryKey    = 'id';
	protected $beforeInsert  = ['formatData'];
	protected $beforeUpdate  = ['formatData'];
	protected $allowedFields = [
		'permission',
		'code',
	];

	protected function formatData($data)
	{
		if (isset($data['data']['permission']))
		{
			$data['data']['permission'] = strtolower($data['data']['permission']);
		}
		return $data;
	}

	function permission($permissonId)
	{
		return $this->where('code', $permissonId)->first();
	}

	function select_where($select, $where)
	{
		return $this->select($select)->where($where)->first();
	}

	function column_where($column, $where)
	{
		return $this->where($where)->findColumn($column);
	}
}
