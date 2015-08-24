<?php

trait DatabaseMysql
{
	public $db = 'mysql';
	private $connection = null;
	private $sql = false;
	private $table = null;

	public function initConnection()
	{	
    $this->connection = new mysqli(Credentials::HOST, Credentials::USER, Credentials::PASS, Credentials::DB);
	}

	public function getSqlError()
	{
		return $this->connection->error;
	}

	public function getAffectedRows()
	{
		return $this->connection->affected_rows;
	}

	public function setTable($table)
	{
		$this->table = $table;
	}

	public function debugSql($boolean)
	{
		$this->sql = $boolean;
	}

	/**
	 * INSERT query
	 * @param  array  $dados array onde keys são os campos da tabela 
	 *  e values as informações que serão inseridas
	 */
	public function insert(array $dados)
	{
		$this->validateTable();

		$keys = $this->getKeysSQLFormated($dados);
		$values = $this->getValuesSQLFormated($dados);

		$query = 'INSERT INTO '.$this->table.'('.$keys.') VALUES('.$values.')';

    if ($this->sql == true) {
        die($query);
    }

		$this->doQuery($query);
	}

	/**
	 * SELECT query
	 * @param  array  $dados campos que devem ser selecionados em query
	 * @param  string $filter contem filtro WHERE
	 */
	public function select($filter, array $dados = null)
	{
		$this->validateTable();
		$this->validateFilter($filter);

		$campos = '*';

		if (is_null($dados) == false) {
			$campos = $this->getValuesSQLFormated($dados);
		}

		$where = $filter['filter'];
		$where = key($where).' = '.$where[key($where)].' ';

		if (isset($filter['options'])) {
			$where .= $this->setOptions($filter['options']);
		}

		$query = 'SELECT '.$campos.' FROM '.$this->table.' WHERE '.$where;

		if ($this->sql == true) {
			die($query);
		}

		return $this->fetchObject($this->doQuery($query));
	}

	private function setOptions($filter)
	{
		$limit = null;
		$string = null;

		if (isset($filter['limit'])) {
			$limit = $filter['limit'];
			unset($filter['limit']);
		}

		$string = 'and ';
		
		foreach ($filter as $key => $value) {
			$string .= ($key == 'level' ? "$key = $value and " : "$key = '$value' and ");
		}

		$string = substr($string, 0, -4);
		
		if (isset($limit)) {
			$string .= 'limit '.$limit;
		}
		
		return $string;
	}

	/**
	 * UPDATE query
	 * @param  string $dados  string contendo campos e valores que serao atualizados
	 * @param  string $filter string com filtro para atualização
	 */
	public function update($dados, $filter)
	{
		$this->validateTable();
		$this->validateFilter($filter);

		$dados = key($dados).' = '.$dados[key($dados)];
		$filter = key($filter).' = '.$filter[key($filter)];

		$query = 'UPDATE ' . $this->table . ' SET ' . $dados . ' WHERE ' . $filter;

		if ($this->sql == true) {
			die($query);
		}

		$this->doQuery($query);
	}

	/**
	 * DELETE query
	 * @param  [type] $filter string contendo filtro para deletar registro
	 */
	public function delete($filter, $limit = 1)
	{
		$this->validateTable();
		$this->validateFilter($filter);
		
		$query = 'DELETE from '.$this->table.' WHERE '.$filter.' limit '.$limit;

		if ($this->sql == true) {
			die($query);
		}

		$this->doQuery($query);
	}

	/**
	 * Executa query
	 * @param  string $query query a ser executada
	 */
	private function doQuery($query)
	{
      $this->table = null;
      return $this->connection->query($query);
	}

	private function fetchObject($result_query)
	{
		if ($result_query->num_rows < 1) {
			return false;
		}

		$return = array();
		while($data = $result_query->fetch_object()){
			$return[] = $data;
		}
		return $return;
	}


	/**
	 * Escapa strings para evitar erros durante 
	 *  manipulação de dados no banco
	 * @param  string $query string com a query a ser escapada
	 * @return string query escapada
	 */
	private function escapeStrings($query)
	{
		return $this->connection->real_escape_string($query);
	}

	/**
	 * Cria formatação de query utilizando keys de array
	 * como valores de campos de tabela do banco
	 * @param  array  $keys array contendo campos e valores do banco
	 * @return string retorna string com formatação para manipulação no banco
	 */
	private function getKeysSQLFormated(array $keys)
	{
		return implode(', ',array_keys($keys));
	}

  /**
   * Cria formatação de query utilizando values de array
   * como valores a serem inseridos ou atualizados no banco
   * @param  array  $values [description]
   * @return [type]         [description]
   */
	private function getValuesSQLFormated(array $values)
	{
		return "'" . implode("', '", array_values($values)) . "'";
	}

	private function validateTable()
	{
		if (is_null($this->table) && $this->sql == true) {
			die('table not informed');
		}
	}

	private function validateFilter($filter)
	{
		if (is_array($filter) == false && $this->sql == true) {
			die('$filter informed is not a valid string');
		}
	}

}
