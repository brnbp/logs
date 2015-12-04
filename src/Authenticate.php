<?php

class Authenticate
{
    const AUTH = 'md5_string_here';

    function __construct()
    {

    }

    protected function authenticate()
    {
        if (isset(getallheaders()['auth']) == false) {
            return false;
        }

        $pass = getallheaders()['auth'];
        $pass = md5(preg_replace("/[^a-z]+/", " ", $pass));

        if ($pass == self::AUTH) {
            $this->setDebugMode();        
            return true;
        }

        return false;
    }

    private function setDebugMode()
    {
        if (isset(getallheaders()['debug'])) {
           setcookie('debug', getallheaders()['debug']);
        }
    }
}

