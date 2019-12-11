<?php namespace App\Controllers;

class Userlogs extends BaseController
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
			$response = $this->respond(['user_logs' => $this->userLogsModel->all()]);
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

	public function user_log($logId)
	{
		$this->authUser(['admin']);
		$response;
		$userLog = $this->userLogsModel->user_log($logId);
		if (! $userLog)
		{
			$response = $this->failNotFound('Log does not exist');
		}
		else if ($this->method === 'get')
		{
			$response = $this->respond(['user_log' => $userLog]);
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

	public function user_logs($userId)
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
			$response = $this->respond(['user_logs' => $this->userLogsModel->select_where(['userId' => $userId])]);
		}
		else
		{
			$response = $this->respond(['message' => 'Method Not Allowed'], 405);
		}

		return $response;
	}

}
