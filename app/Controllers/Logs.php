<?php namespace App\Controllers;

use App\Models\LogsModel;

class Logs extends BaseController
{
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// Do Not Edit This Line
		parent::initController($request, $response, $logger);

		$this->logsModel = new LogsModel();
	}

	public function index()
	{
		$this->authUser(['admin']);
		$response;
		if ($this->method === 'get')
		{
			$response = $this->respond(['logs' => $this->logsModel->findAll()]);
		}
		else if (($this->method === 'post'))
		{
			validateJson($this->data, $this->method);
			$this->validation->run($this->data, 'logs');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				$this->logsModel->insert($this->data);
				$log = $this->logsModel->log($this->data['code']);
				$this->createLog(3, 'created log ' . $log['id']);
				$response = $this->respondCreated(['message' => 'Log Created', 'log' => $log]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

	public function log($logId)
	{
		$this->authUser(['admin']);
		$response;
		$log = $this->logsModel->find($logId);
		if (! $log)
		{
			$response = $this->failNotFound('Log not found!');
		}
		else if ($this->method === 'get')
		{
			$response = $this->respond(['log' => $log]);
		}
		else if (($this->method === 'put') || ($this->method === 'patch'))
		{
			validateJson($this->data, $this->method);
			requiredFields($this->data, ['operation', 'code']);
			$this->validation->run($this->data, 'logUpdate');
			$errors = $this->validation->getErrors();
			if ($errors)
			{
				$response = $this->fail($errors);
			}
			else
			{
				$this->logsModel->update((int)$logId, $this->data);
				$log = $this->logsModel->find($logId);
				$this->createLog(4, 'updated log ' . $log['id']);
				$response = $this->respond(['message' => 'log Updated', 'log' => $log]);
			}
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

}
