<?php

trait DatabaseMysql
{
	public $db = 'mysql';
	private $connection = null;
	private $table = null;

	/** @var string $where string contendo filtros WHERE em query */
	private $where;

	/** @var string $options string contendo opcoes de ordem e limit em query */
	private $options;

	private $database_structure = [
			'id int(11) NOT NULL AUTO_INCREMENT',
			"data_created timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'",
			"updated_in timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP",
			"incidents int(2) NOT NULL DEFAULT '1'",
			'solved int(1) NOT NULL',
			"level int(1) NOT NULL DEFAULT '0'",
			'log_name varchar(50) NOT NULL',
			'identifier varchar(50) NOT NULL',
			'messages mediumtext NOT NULL',
			"site varchar(150) NOT NULL DEFAULT 'padrao'",
			"notification_sent tinyint(1) NOT NULL DEFAULT '0'",
			"PRIMARY KEY (id)"
	];

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

		$this->doQuery($query);
	}

	/**
	 * SELECT query
	 * @param  array $filter contem filtro WHERE
	 * @param  array  $dados campos que devem ser selecionados em query
	 */
	public function select(array $filter, array $dados = null)
	{
		$this->validateTable();
		$this->validateFilter($filter);

		$campos = '*';
		$where = '';

		if (is_null($dados) == false) {
			$campos = $this->getValuesSQLFormated($dados, true);
		}

		$query = 'SELECT '.$campos.' FROM '.$this->table;

		$this->setOptions($filter['options']);

		if (empty($this->where) == false) {
			$query .= ' WHERE '.$this->where;
		}

		$query .= $this->options;
		
		return $this->fetchObject($this->doQuery($query));
	}

	private function setOptions($filter)
	{
		$limit = 25;
    $order = 'DESC';

		if (isset($filter['limit'])) {
			$limit = $filter['limit'];
			unset($filter['limit']);
		}

    if (isset($filter['order'])) {
        $order = $filter['order'];
        unset($filter['order']);
    }

    $this->options = ' order by data_created ' . $order . ' limit ' . $limit;
		
    if (empty($filter)) {
    	return;
    }

		$string = '';

		foreach ($filter as $key => $value) {
			$string .= ($key == 'level' ? "$key = $value and " : "$key = '$value' and ");
		}

		$this->where = substr($string, 0, -4);
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

		$this->doQuery($query);
	}

	/**
	 * Executa query
	 * @param  string $query query a ser executada
	 */
	private function doQuery($query)
	{
  	return $this->connection->query($query);
	}

	private function fetchObject($result_query)
	{
		if ($result_query->num_rows < 1) {
			return false;
		}

		$return = [];
		while($data = $result_query->fetch_object()){
			$return[] = $data;
		}

		return $return;
	}


	/**
     * Escapa strings para evitar erros durante manipulacao de dados via SQL
	 * @param  string $query String com a query a ser escapada
	 * @return string
	 */
	public function escapeStrings($query)
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
	private function getValuesSQLFormated(array $values, $sem_aspas_simples = false)
	{
		if ($sem_aspas_simples) {
			return implode(", ", array_values($values));
		}

		return "'".implode("', '", array_values($values))."'";
	}

	private function validateTable()
	{
		if ($this->tableExists()) {
			return true;
		}

		if ($this->createTable()) {
			return true;
		}

		$this->log_error();
	}

	private function validateFilter($filter)
	{
		if (is_array($filter) == false && $this->sql == true) {
			die('$filter informed is not a valid string');
		}
	}

	/**
	 * Verifica se tabela existe no banco.
	 * 
	 * @return boolean retorna true caso exista, falso caso contrario
	 */
	private function tableExists()
	{
		$this->connection->query("SHOW TABLES LIKE '$this->table'");

		return $this->getAffectedRows() > 0;
	}

	/**
	 * Cria tabela no banco com as colunas necessarias.
	 * 
	 * @return boolean retorna true caso tenha conseguido criar, false caso contrario
	 */
	private function createTable()
	{
		$database_structure = implode(', ', $this->database_structure);

		$query = "CREATE TABLE IF NOT EXISTS $this->table($database_structure)";

		return $this->connection->query($query) === true;
	}

	private function log_error()
	{
		die();
		// CRIAR LOG DE ERRO AO INSERIR LOG
	}

}
