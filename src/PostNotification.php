<?php

class PostNotification extends Logs
{
    /** @not_solved_yet integer log nao resolvido ainda */
    static private $not_solved_yet = 0;

    /** @solved integer  log já resolvido */
    static private $solved = 1;

    /** @in_analysis integer log sendo analisado */
    static private $in_analysis = 2;

    /** @sent integer ocorrencia do log enviado por email */
    static private $sent = 1;

    /** @not_sent integer ocorrencia do log nao enviado por email */
    static private $not_sent = 0;

    /** @nodes_permitted array  */
    static protected $nodes_permitted = [
        'identifier',
        'log_name',
        'level',
        'messages',
        'site'
    ];

    public function __construct()
    {
        $this->db_used = $this->db;
        parent::initConnection();
    }

    /**
     * Metodo responsavel por criar log e retornar a API
     */
    public function create()
    {
        $post_data = $this->validatePost();

        if ($post_data == false) {
            return header('HTTP/1.1 422');
        }

        $this->messages = $post_data['messages'];

        if (is_array($post_data['messages'])) {
            $this->messages = json_encode($post_data['messages']);
        }

        # TODO Serialize the entire POST
        $this->messages = $this->escapeStrings($this->messages);

        $this->identifier = $post_data['identifier'];
        $this->log_name = $post_data['log_name'];
        $this->level = $post_data['level'];
        $this->site = $post_data['site'];

        $insert = $this->makeInsert();

        # TODO Header
        if (is_string($insert)) {
            die($insert);
        }

        header('HTTP/1.1 201');
    }

    /**
     * Valida Requisicao POST JSON 
     */
    private function validatePost()
    {
        $post = json_decode(file_get_contents("php://input"), true);

        if (is_array($post) == false) {
            return false;
        }

        if (isset($post['identifier']) && $post['identifier'] == false) {
            $post['identifier'] = 'empty';
        }

        $to_validate = array_flip(static::$nodes_permitted);

        $sent = array_filter($post);

        if (count(array_diff_key($to_validate, $sent)) > 0) {
            return false;
        }

        return $post;
    }

    /**
     * Insere log no banco de dados
     * @return bool retorna true caso tenha sucesso ou false caso contrario
     */
    private function makeInsert()
    {
        $to_save = [
            'notification_sent' => static::$not_sent,
            'data_created' => date('Y-m-d H:i:s', strtotime('now')),
            'incidents' => 1,
            'updated_in' => '0000-00-00 00-00-00',
            'solved' => static::$not_solved_yet,
            'level' => $this->determineLevel(),
            'log_name' => $this->log_name,
            'identifier' => $this->identifier,
            'messages' => $this->messages,
            'site' => $this->site
        ];

        $this->setTable($this->site);

        if ($this->db_used == 'mongodb') {
            if ($this->verifyExistenceMongo($to_save) == true) {
                return false;
            }

            if ($this->determineLevel() < 3) {
                $this->setNotificationSlack($to_save);
            }
            return $this->insert($to_save);
        }

        if ($this->verifyExistenceMysql() == true) {
            return false;
        }

        if ($this->determineLevel() == 1) {
            $this->setNotificationSlack($to_save);
        }
        
        $this->insert($to_save);

        if ($this->getAffectedRows() > 0) {
            return true;
        }

        return $this->getSqlError();
    }

    /**
     * Verifica se log a ser inserido já existe no banco
     * confirmando as informações enviadas nos seguintes campos
     * site, identifier, log_name e messages
     * @return bool
     */
    private function verifyExistenceMysql()
    {
        if ($this->level == 'info') {
            return false;
        }

        $result_query = $this->select([
            'options' => [
                'identifier' => $this->identifier,
                'log_name' => $this->log_name,
                'messages' => $this->messages,
                'level' => $this->determineLevel()
            ]
        ]);

        if ($result_query == false) {
            return false;
        }

        $result_query = reset($result_query);

        $this->update(['incidents' => ++$result_query->incidents], ['id' => $result_query->id]);

        return true;
    }

    /**
     * Verifica se log a ser inserido já existe no banco
     * confirmando as informações enviadas nos seguintes campos
     * site e messages
     * @return bool
     */
    private function verifyExistenceMongo($to_save)
    {
        $ret = $this->select([
            'filter' => [
                'site' => $this->site, 'messages' => $this->messages
            ]
        ], true);

        if (is_array($ret)) {
            $ret = reset($ret);
            $criteria = ['id' => $ret['id']];
            $to_save['data_created'] = $ret['data_created'];
            $to_save['incidents'] = ++$ret['incidents'];
            $to_save['updated_in'] = date('Y-m-d H:i:s', strtotime('now'));
            $this->update($criteria, $to_save);

            return true;
        }

        return false;
    }
}
