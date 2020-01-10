<?php namespace Config;

class Validation
{
	//--------------------------------------------------------------------
	// Setup
	//--------------------------------------------------------------------

	/**
	 * Stores the classes that contain the
	 * rules that are available.
	 *
	 * @var array
	 */
	public $ruleSets = [
		\CodeIgniter\Validation\Rules::class,
		\CodeIgniter\Validation\FormatRules::class,
		\CodeIgniter\Validation\FileRules::class,
		\CodeIgniter\Validation\CreditCardRules::class,
	];

	/**
	 * Specifies the views that are used to display the
	 * errors.
	 *
	 * @var array
	 */
	public $templates = [
		'list'   => 'CodeIgniter\Validation\Views\list',
		'single' => 'CodeIgniter\Validation\Views\single',
	];

	//--------------------------------------------------------------------
	// Rules
	//--------------------------------------------------------------------

	public $signup = [
		'firstName'   => 'required|alpha|max_length[50]',
		'lastName'    => 'required|alpha|max_length[50]',
		'userName'    => 'required|alpha_dash|max_length[10]|is_unique[users.userName]',
		'email'       => 'required|valid_email|is_unique[users.email]',
		'permissions' => 'required',
		'password'    => 'required|regex_match[/^\S*$/]|min_length[6]|max_length[30]',
	];

	public $login = [
		'userName' => 'if_exist|alpha_dash|max_length[10]',
		'email'    => 'if_exist|valid_email',
		'password' => 'required|regex_match[/^\S*$/]|min_length[6]|max_length[30]',
	];

	public $signup_errors = [
		'userName' => [
			'is_unique' => 'Already exists.',
		],
		'email'    => [
			'is_unique' => 'Already exists.',
		],
		'password' => [
			'regex_match' => 'Should only have aplhanumeric and special characters',
		],
	];

	public $permissions = [
		'permission' => 'required|alpha_dash|max_length[50]|is_unique[permissions.permission]',
		'code'       => 'required|is_natural_no_zero|is_unique[permissions.code]',
	];

	public $permissions_errors = [
		'permission' => [
			'is_unique' => 'Already exists.',
		],
		'code'       => [
			'is_unique' => 'Already exists.',
		],
	];

	public $logs = [
		'operation' => 'required|alpha_dash|max_length[50]|is_unique[logs.operation]',
		'code'      => 'required|is_natural_no_zero|is_unique[logs.code]',
	];

	public $logs_errors = [
		'operation' => [
			'is_unique' => 'Already exists.',
		],
		'code'      => [
			'is_unique' => 'Already exists.',
		],
	];

	public $logUpdate = [
		'operation' => 'if_exist|alpha_dash|max_length[50]|is_unique[logs.operation]',
		'code'      => 'if_exist|is_natural_no_zero|is_unique[logs.code]',
	];

	public $logUpdate_errors = [
		'operation' => [
			'is_unique' => 'Already exists.',
		],
		'code'      => [
			'is_unique' => 'Already exists.',
		],
	];

	public $status = [
		'status' => 'required|alpha_dash|max_length[50]|is_unique[user_status.status]',
		'code'   => 'required|is_natural_no_zero|is_unique[user_status.code]',
	];

	public $status_errors = [
		'status' => [
			'is_unique' => 'Already exists.',
		],
		'code'   => [
			'is_unique' => 'Already exists.',
		],
	];

	public $statusUpdate = [
		'status' => 'if_exist|alpha_dash|max_length[50]|is_unique[user_status.status]',
		'code'   => 'if_exist|is_natural_no_zero|is_unique[user_status.code]',
	];

	public $statusUpdate_errors = [
		'status' => [
			'is_unique' => 'Already exists.',
		],
		'code'   => [
			'is_unique' => 'Already exists.',
		],
	];

	public $update = [
		'firstName'   => 'if_exist|alpha|max_length[50]',
		'lastName'    => 'if_exist|alpha|max_length[50]',
		'userName'    => 'if_exist|alpha_dash|max_length[10]|is_unique[users.userName]',
		'email'       => 'if_exist|valid_email|is_unique[users.email]',
		'permissions' => 'if_exist',
		'password'    => 'if_exist|regex_match[/^\S*$/]|min_length[6]|max_length[30]',
		'permission'  => 'if_exist|alpha_dash|max_length[50]|is_unique[permissions.permission]',
		'code'        => 'if_exist|is_natural_no_zero|is_unique[permissions.code]',
	];

	public $update_errors = [
		'userName'   => [
			'is_unique' => 'Already exists.',
		],
		'email'      => [
			'is_unique' => 'Already exists.',
		],
		'password'   => [
			'regex_match' => 'Should only have aplhanumeric and special characters',
		],
		'permission' => [
			'is_unique' => 'Already exists.',
		],
		'code'       => [
			'is_unique' => 'Already exists.',
		],
	];
}
