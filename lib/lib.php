<?php

function emptyMultidimensionalArray($data)
{
    set_time_limit(5);

    if (is_array($data) == false) {
        printrx(strlen($data));
        return empty($data);
    }


    foreach ($data as $_data) {
        emptyMultidimensionalArray($_data);
    }
}

function printr()
{
    echo '<pre>';

    foreach (func_get_args() as $arg) {
        print_r($arg);
    }

    echo '</pre>';
}

function printrx($data)
{
    if (isset($_COOKIE['debug']) && $_COOKIE['debug']) {
        printr($data);
        die();
    }
}
