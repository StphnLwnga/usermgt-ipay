<?php

use \Firebase\JWT\JWT;

/**
 * Converts and signs a PHP array into a JWT string.
 *
 * @param array $data PHP array
 *
 * @return string   A signed JWT
 */
function generateToken($data)
{
	$issuedAt = time();
	$payload  = [
		'iss'  => 'ipayafrica.com',
		'aud'  => 'users@ipayafrica.com',
		'iat'  => $issuedAt,
		'nbf'  => $issuedAt,
		'exp'  => $issuedAt + HOUR,
		'data' => $data,
	];
	$token    = JWT::encode($payload, $_ENV['JWT_KEY']);

	return $token;
}

/**
 * Validate authorization token.
 *
 * @param string $data String provided from authorization header
 *
 * @return array     decoded user details
 */
function validateToken($data)
{
	if (! $data)
	{
		http_response_code(401);
		exit(json_encode(['error' => 'Provide Accesss Token!']));
	}
	$token = explode(' ', $data);

	if (count($token) !== 2 || $token[0] !== 'Bearer')
	{
		http_response_code(401);
		exit(json_encode(['error' => 'Invalid Accesss Token!']));
	}
	try
	{
		$payload = JWT::decode($token[1], $_ENV['JWT_KEY'], ['HS256']);
	}
	catch (Exception $e)
	{
		http_response_code(401);
		exit(json_encode(['error' => 'Invalid Accesss Token!']));
	}

	return (array)$payload->data;
}

/**
 * Verify if a user exists.
 *
 * @param object $model User model
 * @param array  $data  User details
 *
 * @return array|boolean          User details if user exist or false
 */
function verifyUser($model, $data)
{
	if (isset($data['userName']))
	{
		$user = $model->select_all(['userName' => $data['userName']]);
	}
	else
	{
		$user = $model->select_all(['email' => $data['email']]);
	}

	if ($user)
	{
		if (password_verify($data['password'], $user['password']))
		{
			return $user;
		}
		$user['login'] = 'failed';
		return $user;
	}

	return;
}
