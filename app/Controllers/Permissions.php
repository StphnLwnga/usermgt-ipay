<?php namespace App\Controllers;

class Permissions extends BaseController
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
			$response = $this->respond(['permissions' => $this->permissionsModel->findAll()]);
		}
		else if (($this->method === 'post'))
		{
			validateJson($this->data, $this->method);
			$this->validation->run($this->data, 'permissions');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				$this->permissionsModel->insert($this->data);
				$permission = $this->permissionsModel->permission($this->data['code']);
				$this->createLog(3, 'created permission ' . $permission['id']);
				$response = $this->respondCreated(['message' => 'Permission Created', 'permission' => $permission]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

	public function permission($permissionId)
	{
		$this->authUser(['admin']);
		$response;
		$permission = $this->permissionsModel->find($permissionId);
		if (! $permission)
		{
			$response = $this->failNotFound('Permission not found!');
		}
		else if ($this->method === 'get')
		{
			$response = $this->respond(['permission' => $permission]);
		}
		else if (($this->method === 'put') || ($this->method === 'patch'))
		{
			validateJson($this->data, $this->method);
			requiredFields($this->data, ['permission', 'code']);
			$this->validation->run($this->data, 'update');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				$this->permissionsModel->update((int)$permissionId, $this->data);
				$permission = $this->permissionsModel->find($permissionId);
				$this->createLog(4, 'updated permission ' . $permission['id']);
				$response = $this->respond(['message' => 'Permission Updated', 'permission' => $permission]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

}
