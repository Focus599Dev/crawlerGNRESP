<?php 
include ('anticaptcha/anticaptcha.php');
include ('anticaptcha/imagetotext.php');
include ('crawler.php');	

$data = array(
	"cnpj" 			=> '104642230001-63',
	"tiporecolhimento" => '6|4',
	"uf_fav" 		=> 'SP',
	"incricao_est" 	=> '',
	"nome" 			=> '',
	"endereco" 		=> '',
	"municipio" 	=> '',
	"cep" 			=> '',
	"email" 		=> 'marlon.academi@gail.com',
	"nfe" 			=> '',
	'cnpj_rem' 		=> '',
	'inf_comp' 		=> 'Teste de PDF',
	'data_venc' 	=> '20/09/2018',
	'data_ref' 		=> '09/2018',
	'valor_prin'	=> '2,00',
	'juros'			=> '0,00',
	'multa' 		=> '0,00',
	'atua_monet'	=> '0,00',
	'total'			=> '2,00'
);


$cw = new Crawler($data);

// Criar conta em https://anti-captcha.com
$cw->setKeyCaptch('5f9d7d984ed1405536544ddd7b244c6e');

$cw->getBoleto();

?>