<?php namespace App\Controllers;

class Users extends BaseController
{
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);
	}

	public function index()
	{
		$this->authUser(['admin']);
		$response;
		if ($this->method === 'get')
		{
			$users = $this->userModel->all();
			foreach ($users as &$user)
			{
				$user['permissions'] = getPermissionNames($this->permissionsModel, $user['permissions']);
			}
			unset($user);
			$response = $this->respond(['users' => $users]);
		}
		else if (($this->method === 'post'))
		{
			validateJson($this->data, $this->method);
			$this->validation->run($this->data, 'signup');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				getPermissionNames($this->permissionsModel, $this->data['permissions'], true);
				$this->userModel->insert($this->data);
				$user = $this->userModel->select_where(['email' => $this->data['email']]);
				$this->createLog(1, 'created user ' . $user['id']);
				$response = $this->respondCreated(['message' => 'User Created', 'user' => $user]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

	public function user($userId)
	{
		$this->authUser(['admin']);
		$response;
		$user = $this->userModel->user($userId);
		if (! $user)
		{
			$response = $this->failNotFound('User not found!');
		}
		else if ($this->method === 'get')
		{
			$user['permissions'] = getPermissionNames($this->permissionsModel, $user['permissions']);
			$response            = $this->respond(['user' => $user]);
		}
		else if (($this->method === 'put') || ($this->method === 'patch'))
		{
			validateJson($this->data, $this->method);
			$this->validation->run($this->data, 'update');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				if (isset($this->data['permissions']))
				{
					getPermissionNames($this->permissionsModel, $this->data['permissions'], true);
				}
				if (isset($this->data['status']))
				{
					statusExists($this->statusModel, $this->data['status']);
				}
				$this->userModel->update((int)$userId, $this->data);
				$user                = $this->userModel->user($userId);
				$user['permissions'] = getPermissionNames($this->permissionsModel, $user['permissions']);
				$this->createLog(4, 'updated user ' . $user['id']);
				$response = $this->respond(['message' => 'User Updated', 'user' => $user]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

	public function login()
	{
		$response;
		if (($this->method === 'post'))
		{
			validateJson($this->data, $this->method);
			$this->validation->run($this->data, 'login');
			$errors = $this->validation->getErrors();
			if (empty($this->data['userName']) && empty($this->data['email']))
			{
				$response = $this->failValidationError('Provide username or email');
			}
			else if ($errors)
			{
				$response = $this->failValidationError('Invalid username or password!');
			}
			else
			{
				$user = verifyUser($this->userModel, $this->data);
				if (isset($user['login']))
				{
					unset($user['password']);
					$this->current_user = $user;
					$this->createLog(2, $user['login']);
					$response = $this->failValidationError('Invalid username or password!');
				}
				else if (! $user)
				{
					$response = $this->failValidationError('Invalid username or password!');
				}
				else
				{
					$login_user                = $this->userModel->user($user['id']);
					$login_user['permissions'] = getPermissionNames($this->permissionsModel, $login_user['permissions']);
					$this->current_user        = $login_user;
					$this->createLog(2, 'successful');
					$token    = generateToken($login_user);
					$response = $this->respond(['message' => 'Login successful!', 'token' => $token]);
				}
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}
}
