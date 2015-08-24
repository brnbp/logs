<?php

trait DatabaseMongo
{
	public $db = 'mongodb';
	private $connection = null;
	private $database = null;
	private $collection = null;

	private $limit = null;

	public function initConnection()
	{
    $this->connection = new MongoClient(); // localhost

		$this->database = $this->connection->notification;

		$this->collection = $this->database->logs;
	}

	/**
	 * INSERT query
	 * @param  array  $dados array onde keys são os campos da tabela 
	 *  e values as informações que serão inseridas
	*/
	public function insert(array $dados)
	{
		$insert = $this->collection->insert($dados);
		//$insert = $this->collection->update($verify, $dados, ['upsert' => true]);

    if ($insert['ok']) {
      return true;
    }

		return false;
	}

	public function update(array $criteria, array $data)
	{
		$criteria['_id'] = new MongoID($criteria['id']);
    unset($criteria['id']);

		$update = $this->collection->update($criteria, $data);

		if ($update['nModified'] > 0) {
			return true;
		}

		return false;
	}

	/**
	 * SELECT query
	 * @param  array  $dados campos que devem ser selecionados em query
	 * @param  string $filter contem filtro WHERE
	 */
	public function select($filter, $return_id = false)
	{
		if (isset($filter['options']['limit'])) {
			$this->limit = $filter['options']['limit'];
			unset($filter['options']['limit']);
		}

		$filter = $this->setFilter($filter);
			
		$select = $this->fetchObject($this->collection->find($filter), $return_id);

		return $select;
	}

	private function setFilter($filter)
	{
		$options = [];
		if (isset($filter['options'])) {
			$options = $filter['options'];
		}

		$filter = $filter['filter'];
		$filter = str_replace("'", '', $filter);

		return $filter += $options;
	}

	/**
	 * DELETE query
	 * @param  [type] $filter string contendo filtro para deletar registro
	 */
	public function delete($filter, $limit = 1)
	{
			// TODO
	}

	private function fetchObject($result_query, $return_id)
	{
		if ($result_query->count() < 1) {
			return false;
		}
	
		if (is_null($this->limit) == false) {
			$result_query->limit($this->limit);
			$this->limit = null;
		}

		foreach ($result_query as $key => $value) {
			unset($value['_id']);
			if ($return_id == true) {
				$value['id'] = $key;
			}
			$result[] = $value;
		}

		return $result;
	}

}
