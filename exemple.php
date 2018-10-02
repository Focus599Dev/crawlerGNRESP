<?php 
include ('anticaptcha/anticaptcha.php');
include ('anticaptcha/imagetotext.php');
include ('crawler.php');	

$data = array(
	'tiporecolhimento' => '6|4',,
	'Item2.Email.Texto' => '',
	'Item2.NotaFiscalEletronica.Texto' => '',
	'Item2.CnpjRemetente.Texto' => '',
	'Item2.CnpjDestinatario.Texto' => '',
	'Item2.InformacoesComplementares.Texto' => '',
	'Item2.DataDeVencimento.Texto' => '',
	'Item2.Referencia.Texto' => '',
	'Item2.ValorPrincipal.Texto' => '',
	'Item2.Juros.Texto' => '',
	'Item2.Multa.Texto' => '',
	'Item2.AtualizacaoMonetaria.Texto' => '',
	'Item2.Total.Texto' => '',
);


$cw = new Focus599Dev\CrawlerSP\Crawler($data);

// Criar conta em https://anti-captcha.com
$cw->setKeyCaptch('5f9d7d984ed1405536544ddd7b244c6e');

$cw->getBoleto();

?>
