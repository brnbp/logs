<?php
error_reporting(E_ALL);

class HttpData
{
	use Database;
    /**
   * @return [type]
   */
    protected function createNotification()
    {
        $post_data = $this->validatePost();

        if ($post_data == false) {
            return header('HTTP/1.1 422');
        }

        $this->messages = $post_data['messages'];

        if (is_array($post_data['messages'])) {
            $this->messages = json_encode($post_data['messages']);
        }

        $this->identifier = $post_data['identifier'];
        $this->log_name = $post_data['log_name'];
        $this->level = $post_data['level'];
        $this->site = $post_data['site'];

        $insert = $this->makeInsertSQL();

        # TODO Header
        if (is_string($insert)) {
            die($insert);
        }

        header('HTTP/1.1 201');
    }

    /**
     * @return [type]
     */
	private function validatePost()
	{
		$post = json_decode(file_get_contents("php://input"), true);

        if (is_array($post) == false) {
            return false;
        }

        $to_validate = array_flip(static::$nodes_permitted);
        $sent = array_filter($post);

		if (count(array_diff_key($to_validate, $sent)) > 0) {
            return false;
        }

        return $post;
	}


    /**
     * @return [type]
     */
	private function determineLevel($level = null)
	{
		if (is_null($level) == false) {
			$this->level = $level;
		}

		$this->level = trim(strtolower($this->level));

        if (array_key_exists($this->level, static::$levels)) {
          return static::$levels[$this->level];    
        }

      	return false;
	}

    /**
     * @return [type]
     */
	private function makeInsertSQL()
	{
		$to_save = [
			'notification_sent' => static::$not_sent,
			'data_created' => date('Y-m-d H:i:s', strtotime('now')),
			'solved' => static::$not_solved_yet,
			'level' => $this->determineLevel(),
			'log_name' => $this->log_name, 
			'identifier' => $this->identifier,
			'messages' => $this->messages,
			'site' => $this->site
		];

        if ($this->verifyExistence() == true) {
            return false;    
        }

		$this->setTable('notifications');
        # $this->debugSql(true);
        $this->insert($to_save);

		if ($this->getAffectedRows() > 0) {
			return true;
		}
		
		return $this->getSqlError();
	}

    /**
     * @return [type]
     */
    private function verifyExistence()
    {
        $this->setTable('notifications');

        $filter = [
            'filter' => [
                'messages' => "'$this->messages'",
                'site' => "'$this->site'"
            ]
        ];

        $result_query = $this->select($filter);
        if ($result_query == false) {
            return false;
        }

        $result_query = reset($result_query);

        $this->setTable('notifications');
        $this->update(['incidents' => ++$result_query->incidents], ['id' => $result_query->id]);

        return true;
    }

    protected function getNotification($filter, $parameters)
    {
        $filter = $this->setFilters($filter, $parameters);

        if ($filter ==  false) {
            return header('HTTP/1.1 422');
        }

        //$this->debugSql(true);
        $this->setTable('notifications');
        $result_query = $this->select($filter);

        if ($result_query == false) {
            return header('HTTP/1.1 404');            
        }

        header('HTTP/1.1 200');
        header('Content-Type: application/json');

        echo json_encode($result_query);
    }


    private function setFilters($filter, $parameters)
    {
		$params_valids = ['level', 'site', 'logName'];
		if (in_array($filter, $params_valids) == false) {
			return false;
		}

        $parameters = explode('&', $parameters);

        $filters = $this->arrayFilter($this->getAssocArray($parameters));

        if (isset($filters['limit'])) {
        	if (is_numeric($filters['limit']) == false) {
        		return false;
        	}
      	}

      	if (isset($filters['level'])) {
        	$filters['level'] = $this->determineLevel($filters['level']);
        }

      	if (empty($filters) == false) {
        	$returns['options'] = $filters;
        }

      	if ($filter == 'level'){
          $parameters[0] = $this->determineLevel($parameters[0]);

	        if ($parameters[0] == false) {
            return false;
          }
        }

        if ($filter == 'logName') {
        	$filter = 'log_name';
        }

      	$returns['filter'] = [
          $filter => is_numeric($parameters[0]) ? $parameters[0] : "'$parameters[0]'"
        ];

        $returns = $this->removeDuplicateEntries($returns);

        return $returns;
    }

    private function removeDuplicateEntries(array $array)
    {
    	if (isset($array['options'][strtolower(key($array['filter']))]) == false) {
    		return $array;
    	}

    	unset($array['options'][strtolower(key($array['filter']))]);

    	return $array;
    }

    private function getAssocArray($parameter)
    {
    	$master_filter = array();
    	foreach ($parameter as $filtros) {
        if (strpos($filtros, '=') == false) {
            continue;
        }

        $filtro = explode('=', $filtros);

        $master_filter[$filtro[0]] = $filtro[1];
      }
      return $master_filter;
    }

    private function arrayFilter($array)
    {
    	if (isset($array['logname'])) {
    		$array['log_name'] = $array['logname'];
    		unset($array['logname']);
    	}
    	$filters_allowed = array_flip(
    		[
        	'level', 'site', 'log_name', 'limit'
        ]
      );

      return array_intersect_key($array, $filters_allowed);
    }

}