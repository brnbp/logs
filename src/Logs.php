<?php
error_reporting(E_ALL);

class Logs extends HttpData
{

   /**
   * [$messages description]
   * @var string
   */
	protected $messages = '';

   /**
   * [$identifier description]
   * @var string
   */
	protected $identifier = '';

   /**
   * [$log_name description]
   * @var string
   */
	protected $log_name = '';

   /**
   * [$level description]
   * @var string
   */
   protected $level = '';

  /**
  * [$site description]
  * @var string
  */
  protected $site = '';

  /**
   * log nao resolvido ainda
   * @var integer
   */
	static protected $not_solved_yet = 0;

  /**
   * log já resolvido
   * @var integer
   */
	static protected $solved = 1;

  /**
   * log sendo analisado
   * @var integer
   */
	static protected $in_analysis = 2;

  /**
   * ocorrencia do log enviado por email
   * @var integer
   */
	static protected $sent = 1;

  /**
   * ocorrencia do log nao enviado por email
   * @var integer
   */
	static protected $not_sent = 0;

	/**
	* [$levels description]
	* @var [type]
	*/
	static protected $levels = [
		'critical' => 1,
		'warning' => 2,
		'info' => 3
	];

    /**
     * [$nodes_permitted description]
     * @var [type]
     */
    static protected $nodes_permitted = [
        'identifier',
        'log_name',
        'level',
        'messages',
        'site'
    ];

    const AUTH = '2e134ad1281e029e675ede83fbfa32bd';

    /**
     * @param  [type]
     * @param  [type]
     * @return [type]
     */
	public function notification($filter = null, $param = null)
	{
      if ($this->authenticate() == false) {
          return header('HTTP/1.1 403 You Shall Not Pass');
      }

      if (func_num_args() > 2) {
          return header('HTTP/1.1 422 Number of Resources Not Allowed');
      }

      $this->initConnection();

      switch($_SERVER['REQUEST_METHOD']) {
          case 'POST':
            $this->createNotification();
            break;
          case 'GET':
            $this->getNotification($filter, $param);
      }
	}

    /**
     * @return [type]
     */
    private function authenticate()
    {
        if (isset(getallheaders()['auth']) == false) {
            return false;
        }

        $pass = getallheaders()['auth'];
        $pass = md5(preg_replace("/[^a-z]+/", " ", $pass));

        if ($pass == self::AUTH) {
            return true;
        }
     
        return false;
    }

}
