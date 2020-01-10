<?php
namespace App\Controllers;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 *
 * @package CodeIgniter
 */

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\PermissionsModel;
use App\Models\StatusModel;
use App\Models\UserLogsModel;

class BaseController extends Controller
{

	use ResponseTrait;

	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var array
	 */

	protected $helpers = [
		'confirmation',
		'auth',
	];

	/**
	 * Constructor.
	 */
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		//--------------------------------------------------------------------
		// Preload any models, libraries, etc, here.
		//--------------------------------------------------------------------
		// E.g.:
		// $this->session = \Config\Services::session();
      	//header('Access-Control-Allow-Origin: http://localhost:3000');
        header('Access-Control-Allow-Origin: *');
      	header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
      	header('Access-Control-Allow-Headers: append,delete,entries,foreach,get,has,keys,set,values,Authorization');
      	header('Access-Control-Allow-Credentials: true');
      	header('Access-Control-Allow-Headers: Content-Type');
		header('Content-Type: application/json');
		$this->validation       = \Config\Services::validation();
		$this->userModel        = new UserModel();
		$this->statusModel      = new StatusModel();
		$this->permissionsModel = new PermissionsModel();
		$this->userLogsModel    = new UserLogsModel();
		$this->headers          = getallheaders();
		$this->current_user     = [];
		$this->method           = $this->request->getMethod();
		$this->data             = $this->request->getJSON(true);
	}

	/**
	 * Create User logs
	 *
	 * @param integer $logCode     The Id for log type
	 * @param string  $description The description of operation performed
	 *
	 * @return void
	 */
	public function createLog($logCode, $description)
	{
		$userLog = [
			'userId'      => (int)$this->current_user['id'],
			'logCode'     => (int)$logCode,
			'description' => $description,
			'userIP'      => $this->request->getIPAddress(),
		];
		$this->userLogsModel->insert($userLog);
	}

	/**
	 * Authenticate User
	 *
	 * @param array $authorized List of permissions allowed
	 *
	 * @return void
	 */

	public function authUser($authorized)
	{
		if (empty($this->headers['Authorization']))
		{
			http_response_code(401);
			exit(json_encode(['error' => 'Provide Accesss Token!']));
		}

		$data        = validateToken($this->headers['Authorization']);
		$permissions = getPermissionCodes($this->permissionsModel, $data['permissions']);
		if ((int)$permissions === 0)
		{
			http_response_code(401);
			exit(json_encode(['error' => 'Invalid Accesss Token!']));
		}
		elseif ((int)$permissions === 2)
		{
			http_response_code(401);
			exit(json_encode(['error' => 'You are not Authorized!']));
		}

		$user = $this->userModel->select_all(['userName' => $data['userName'], 'permissions' => $permissions]);
		if (! $user)
		{
			http_response_code(401);
			exit(json_encode(['error' => 'Invalid Accesss Token!']));
		}

		if (array_diff($authorized, $data['permissions']))
		{
			http_response_code(401);
			exit(json_encode(['error' => 'You are not Authorized!']));
		}

		$this->current_user = $data;
	}
}
