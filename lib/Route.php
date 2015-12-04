<?php

class Route
{
	/** @var array Classes URN's (example/{class}/method) */
	private $class_resources = [];

	/** @var array Classes filenames */
	private $class_names = [];

	/**
	 * @TODO DOC
	 * @param array $resources Resources of a class to add, like '/example'
	 */
	public function addClassResource($resources)
	{
		foreach ($resources as $resource) {
            $this->class_resources[] = mb_strtolower(rtrim($resource, '/'));
			$this->class_names[] = ucfirst(str_replace('/', '', $resource));
		}
	}

	/**
	 * @TODO DOC
	 */
	public function submit()
	{
		if (isset($_SERVER['REDIRECT_QUERY_STRING']) == false) {
			return header('HTTP/1.1 404 Resource Not Found');
		}

		$parameters = explode('/', strtr($_SERVER['REDIRECT_QUERY_STRING'], ['uri=' => '']));

		$class_invoked = '/' . mb_strtolower($parameters[0]);

        	$method_invoked = empty($parameters[1]) ? null : $parameters[1];

		$parameters = array_slice($parameters, 2);

		$index = in_array($class_invoked, $this->class_resources);

		if ($index === false) {
			return header('HTTP/1.1 404 Resource Not Found');
		}

		$class_invoked = ucfirst(substr($class_invoked, 1));

		if (class_exists($class_invoked) == false) {
			return header('HTTP/1.1 404 Resource Not Found');
		}

		$instance = new $class_invoked;

		if (method_exists($class_invoked, $method_invoked) == false) {
			return header('HTTP/1.1 404 Resource Not Found');
		}

		call_user_func_array([$instance, $method_invoked], $parameters);
	}
}
