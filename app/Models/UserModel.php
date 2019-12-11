<?php namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model {

	protected $table         = 'users';
	protected $primaryKey    = 'id';
	protected $beforeInsert  = ['onInsert'];
	protected $beforeUpdate  = ['onUpdate'];
	protected $allowedFields = [
		'firstName',
		'lastName',
		'userName',
		'email',
		'permissions',
		'password',
		'status',
	];

	protected function formatData($data)
	{
		if (isset($data['data']['firstName']))
		{
			$data['data']['firstName'] = strtolower($data['data']['firstName']);
		}
		if (isset($data['data']['lastName']))
		{
			$data['data']['lastName'] = strtolower($data['data']['lastName']);
		}
		if (isset($data['data']['userName']))
		{
			$data['data']['userName'] = strtolower($data['data']['userName']);
		}
		if (isset($data['data']['password']))
		{
			$data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
		}
		return $data;
	}

	protected function onInsert($data)
	{
		$new_data                   = $this->formatData($data);
		$new_data['data']['status'] = 1;

		return $new_data;
	}

	protected function onUpdate($data)
	{
		return $this->formatData($data);
	}

	protected function select_options()
	{
		$select = 'users.id,users.firstName,users.lastName,users.userName,users.email,users.permissions,user_status.status';
		$this->select($select)->where('users.status !=', 2)->join('user_status', 'users.status = user_status.code');
	}

	function all()
	{
		$this->select_options();
		return $this->findAll();
	}

	function select_all($where)
	{
		$where += ['status !=' => 2];
		return $this->where($where)->get()->getRowArray();
	}

	function user($userId)
	{
		$this->select_options();
		return $this->find($userId);
	}

	function select_where($where)
	{
		$this->select_options();
		return $this->where($where)->first();
	}
}
