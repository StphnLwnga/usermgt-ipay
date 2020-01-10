<?php namespace App\Controllers;

class Userstatus extends BaseController
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
			$response = $this->respond(['user_status' => $this->statusModel->findAll()]);
		}
		else if (($this->method === 'post'))
		{
			validateJson($this->data, $this->method);
			$this->validation->run($this->data, 'status');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				$this->statusModel->insert($this->data);
				$status = $this->statusModel->status($this->data['code']);
				$this->createLog(3, 'created status ' . $status['id']);
				$response = $this->respondCreated(['message' => 'Status Created', 'status' => $status]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

	public function status($statusId)
	{
		$this->authUser(['admin']);
		$response;
		$status = $this->statusModel->find($statusId);
		if (! $status)
		{
			$response = $this->failNotFound('Status not found!');
		}
		else if ($this->method === 'get')
		{
			$response = $this->respond(['status' => $status]);
		}
		else if (($this->method === 'put') || ($this->method === 'patch'))
		{
			validateJson($this->data, $this->method);
			requiredFields($this->data, ['status', 'code']);
			$this->validation->run($this->data, 'statusUpdate');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				$this->statusModel->update((int)$statusId, $this->data);
				$status = $this->statusModel->find($statusId);
				$this->createLog(4, 'updated status ' . $status['id']);
				$response = $this->respond(['message' => 'Status Updated', 'status' => $status]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

}
