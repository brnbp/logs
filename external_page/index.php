<?php


/**
 * SCRIPT TO TEST THE WHOLE STUFF
 * @var integer
 */
for($i = 1; $i <= 1; $i++):

	$cod_produto = str_replace('.', '', rand($i, 500) / ($i * ($i^10)+1*50));

	if ($i % 2 == 0) {
		$erro = [
			'identifier' => 'Marketplace - Cnova',
			'log_name'   => 'cnova_produto_sem_estoque',
			'level'		   => 'warning',
			'messages'   => 'O produto '.$cod_produto.' nao teve estoque atualizado',
			'site'   => 'titanis'
		];

	} else {
		$erro = [
			'identifier' => 'Marketplace - Cnova',
			'log_name'   => 'cnova_pedido_sem_produto',
			'level'		   => 'critical',
			'messages'   => 'O pedido '.$cod_produto.' nao possui itens relacionados a ele',
			'site'   => 'continentalcenter'
		];
	}

	printr('<br><hr><br><b>Http Code Response: '.set_log($erro)['http_code']);

endfor;

/**
 * END SCRIPT
 */


/**
 * STUFF TO SEND CURL POST WITH LOG DATA
 */

/**
 * DO SOMETHING AND SEND LOG VIA POST
 * @param array $logData content with details of the log
 */
function set_log(array $logData)
{
	$log_name = $logData['level'].'_'.$logData['log_name'];

	// PATH TO WHERE API IS HOSTED
	$url = '{{url-api-path}}/logs/notification';
	return do_post($logData, $url);
}


/**
 * make POST via CURL
 * @param  array  $data log stuff
 * @param  string $url  uri where the api is host
 * @return to-think-what-to-put-here
 */
function do_post(array $data, $url)
{
	$data = json_encode(array_map('addslashes', $data));

	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 5);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER,  array(
                'Content-type: application/json',
                'Accept: application/json',
                'auth: {{pass-auth}}'
            ));

	$result = curl_exec($ch);

	$resposta = curl_getinfo($ch);

	curl_close($ch);

	return $resposta;
}



/**
 * functions just fot help the debug 
 */

function printr($string)
{
	echo '<pre>';
	print_r($string);
	echo '</pre>';
}

function printrx($string)
{
	printr($string);
	die();
}
