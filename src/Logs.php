<?php
#error_reporting(E_ALL);

class Logs extends Authenticate
{

    use DatabaseMysql;
    # use DatabaseMongo;

    protected $db_used = null;

    /** @var  SLACK_WEB_HOOK url to use bot on slack */
    const SLACK_WEB_HOOK = 'insert_here';

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
     * [$levels description]
     * @var [type]
    */
    static protected $levels = [
    	'critical' => 1,
    	'warning' => 2,
    	'info' => 3
    ];

    /**
     * metodo responsavel por receber get/post da api
     * e gerenciar para o tratamento necessario
     */
    public function notification($filter = null, $param = null)
  	{
        if ($this->authenticate() == false) {
            return header('HTTP/1.1 403 You Shall Not Pass');
        }

        if (func_num_args() > 2) {
            return header('HTTP/1.1 422 Number of Resources Not Allowed');
        }

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
              $this->Post = new PostNotification();
              $this->Post->create();
              break;

            case 'GET':
                $this->Get = new GetNotification();
                $this->Get->notifications($filter, $param);
                break;
        }
  	}

    /**
     * Determina o level do log de acordo com a string fornecida
     * @param  string $level string contendo nivel do log
     */
    protected function determineLevel($level = null)
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
     * Envia Notificação para o Slack ao ocorrer log Critical
     * @param array $data array com as informações do log critical que foi inserido
     */
    protected function setNotificationSlack($data)
    {
        $subject = $data['log_name'];
        if (strpos($data['log_name'], '_') == true) {
            $subject = strstr($data['log_name'], '_', true);
        }

        $color = '';
        switch ($this->determineLevel()) {
            case 1:
                $color = 'danger';
                break;
            case 2:
                $color = '#F2D600';
                break;
            default:
                $color = '#00C2E0';
                break;
        }

        $message = json_encode(
            [
                'channel' => "#logs",
                'username' => strtoupper($subject),
                'icon_url' => 'cdn_path_here/logs/img/'.$subject.'.png',
                'attachments' => [
                    [
                        'title' => "SITE: ".$data['site']." | LOGNAME: ".$data['log_name'],
                        'text' => 'Identifier: ' . $data['identifier'],
                        'color' => $color,
                        'fields' => [
                            [
                                'value' => substr(stripslashes($this->messages), 0, 700),
                                'short' => true
                            ]
                        ]
                    ]
                ],
            ]
        );

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => "payload=$message"
            ]
        ];
        $context  = stream_context_create($opts);
        $ret = file_get_contents(self::SLACK_WEB_HOOK, false, $context);
    }

}
