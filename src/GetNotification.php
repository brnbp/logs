<?php

class GetNotification extends Logs
{
    private $site_table;

    public function __construct()
    {
        $this->db_used = $this->db;
        parent::initConnection();
    }

    protected function notifications($filter, $parameters)
    {
        $filter = $this->setFilters($filter, $parameters);

        if ($filter ==  false) {
            return header('HTTP/1.1 400');
        }

        if ($this->db_used == 'mysql') {
            $this->setTable($this->site_table);
        }

        $result_query = $this->select($filter);

        if ($result_query == false) {
            return header('HTTP/1.1 404');
        }

        header('HTTP/1.1 200');
        header('Content-Type: application/json');
        
        foreach($result_query['results'] as $result) {
            $result->messages = json_decode($result->messages, true);
        }

        echo json_encode($result_query);
    }

    private function setFilters($filter, $get_parameters)
    {
        $params_valids = ['site'];

        if (in_array($filter, $params_valids) == false) {
            return false;
        }

        $get_parameters = explode('&', $get_parameters);

        $filters = $this->arrayFilter($this->getAssocArray($get_parameters));

        if (isset($filters['limit'])) {
            if (is_numeric($filters['limit']) == false) {
                return false;
            }

            if ($filters['limit'] > 100) {
                $filters['limit'] = 100;
            }
        } else {
            $filters['limit'] = 25;
        }

        if (isset($filters['level'])) {
            $filters['level'] = $this->determineLevel($filters['level']);
        }

        if (empty($filters) == false) {
            $returns['options'] = $filters;
        }

        if ($filter == 'level'){
            $get_parameters[0] = $this->determineLevel($get_parameters[0]);

            if ($get_parameters[0] == false) {
                return false;
            }
        }

        if (isset($filters['order'])){
            $filters['order'] = mb_strtoupper($filters['order']);
            if ($filters['order'] != 'DESC' && $filters['order'] != 'ASC') {
                return false;
            }
        }

        return $returns;
    }

    private function getAssocArray($parameter)
    {
        $master_filter = [];
        foreach ($parameter as $filtros) {
            if (strpos($filtros, '=') == false) {
                $this->site_table = $filtros;

                continue;
            }

            $filtro = explode('=', $filtros);

            $master_filter[$filtro[0]] = $filtro[1];
        }

        return $master_filter;
    }

    private function arrayFilter($array)
    {
        $filters_allowed = array_flip([
            'level',
            'log_name',
            'limit',
            'identifier',
            'order'
        ]);

        return array_intersect_key($array, $filters_allowed);
    }
}
