<?php

/**
 * Validate Json recieved if it is not empty and valid
 *
 * @param array  $data   Array of data obtained by converting recieved Json data
 * @param string $method The http method ued to end request
 *
 * @return void
 */

function validateJson($data, $method)
{
	if ((! is_array($data) || empty($data)) && ($method === 'put' || $method === 'patch' || $method === 'post'))
	{
		http_response_code(400);
		exit(json_encode(['error' => 'Invalid or empty JSON!']));
	}
}

/**
 * Confirm at least one of available fields is set during update
 *
 * @param array $data     Array of data obtained by converting recieved Json data
 * @param array $required List of all available fields for update
 *
 * @return void
 */
function requiredFields($data, $required)
{
	if (count(array_diff_key(array_flip($required), $data)) === count($required))
	{
		$fields = implode('|', $required);
		http_response_code(400);
		exit(json_encode(['error' => "provide either $fields to update!"]));
	}
}

/**
 * Confirm that status code provided by the user exists in the database
 *
 * @param object  $model Status Model
 * @param integer $code  Status code to validate
 *
 * @return void
 */
function statusExists($model, $code)
{
	if (! $model->status($code))
	{
		http_response_code(400);
		exit(json_encode(['error' => 'Status does not exist!']));
	}
}

/**
 * Get Names to user permissions whose codes are provided
 *
 * @param object  $model       Permissions Model
 * @param string  $permissions String of Comma seperated permission codes
 * @param boolean $error       True or false condition whether to return http error or not
 *
 * @return array          List of permission name
 */
function getPermissionNames($model, $permissions, $error = false)
{
	$permissions = explode(',', $permissions);
	$allCodes    = $model->findColumn('code');
	if (array_diff($permissions, $allCodes))
	{
		if ($error)
		{
			http_response_code(400);
			exit(json_encode(['error' => 'Invalid permissions!']));
		}
		$permissionNames = ['invalid'];
	}
	elseif ((in_array('1', $permissions) || in_array('2', $permissions)) && count($permissions) > 1)
	{
		if ($error)
		{
			http_response_code(400);
			exit(json_encode(['error' => 'Invalid permissions!']));
		}
		$permissionNames = ['invalid'];
	}
	else
	{
		$permissionNames = $model->column_where('permission', 'code IN (' . implode(',', $permissions) . ')');
	}

	return $permissionNames;
}

/**
 * Get Codes to user permissions whose Names are provided
 *
 * @param object $model       Permissions Model
 * @param array  $permissions List of permission name
 *
 * @return string          String of Comma seperated permission codes
 */
function getPermissionCodes($model, $permissions)
{
	$allNames = $model->findColumn('permission');
	if (array_diff($permissions, $allNames))
	{
		$permissionCodes = [0];
	}
	elseif (in_array('none', $permissions))
	{
		$permissionCodes = [2];
	}
	elseif (in_array('admin', $permissions))
	{
		$permissionCodes = [1];
	}
	else
	{
		$permissionCodes = $model->column_where('code', "permission IN ('" . implode("','", $permissions) . "')");
	}

	return implode(',', $permissionCodes);
}
