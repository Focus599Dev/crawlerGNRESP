<?php 

namespace Focus599Dev\CrawlerSP;

include_once realpath(__DIR__ . '/../anticaptcha') . '/anticaptcha.php';

include_once realpath(__DIR__ . '/../anticaptcha') . '/imagetotext.php';

use AntiCaptcha\ImageToText;
use DOMDocument;
use DomXpath;

class Crawler{

	protected $url_base = 'https://www10.fazenda.sp.gov.br';

	protected $url_capcha = 'https://www10.fazenda.sp.gov.br/GeradorIntegradoGuias/';
	
	protected $url_boleto = 'https://www10.fazenda.sp.gov.br/GeradorIntegradoGuias/GeradorGnre/ExibirFormulario';

	protected $url_post_pdf = 'https://www10.fazenda.sp.gov.br/GeradorIntegradoGuias/GeradorGnre/GerarGuia';

	protected $text_html = '';

	protected $html;

	protected $patch_captcha;

	protected $data = array();

	protected $filePDF;

	protected $keyCaptch;

	protected $postBeforeCapcth = array(
		'radioOpcaoDocumento' => 'radioCnpj',
		'documento' => '',
		'CaptchaDeText' => '',
		'CaptchaInputText' => '',
	);

	protected $postBoleto = array(
		'hfUltimaDataDeVencimento' => '',
		'tiporecolhimento' => '',
		'Item2.InscricaoEstadual.Texto' => '',
		'Item2.Nome.Texto' => '',
		'Item2.Endereco.Texto' => '',
		'Item2.Municipio.Texto' => '',
		'Item2.EstadoEscolhido' => 'SP',
		'Item2.Cep.Texto' => '',
		'Item2.Telefone.Texto' => '',
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

	protected $header = array(
		    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
		    'Accept-Encoding: gzip, deflate, br',
		    'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
		    'Cache-Control: no-cache',
		    'Host: www10.fazenda.sp.gov.br',
		    'Pragma: no-cache',
		    'Referer: https://www10.fazenda.sp.gov.br/GeradorIntegradoGuias/GeradorGNRE/pesquisar',
		    'Upgrade-Insecure-Requests: 1',
		    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36'
	);

	function __construct($data){

		set_time_limit(0);

		error_reporting(1);

		$this->clearSessionCurl();

		$this->data = $data;

		if (session_status() == PHP_SESSION_NONE)
            session_start();
	}

	public function getBoleto(){

		$html = $this->execCurl($this->url_capcha, 'GET', null);

		$this->text_html = $html;

		$this->html = new DOMDocument();

		$this->html->loadHTML($this->text_html);
		
		$path = $this->getCaptcha($this->html);

		if (!$path){

	        $this->logError('Não foi possivel achar a imagem do captch');

			return false;
		}

		$this->postBeforeCapcth = $this->fillPost($this->postBeforeCapcth);

		$text_capcth = $this->resolveCaptcha($path);

		$this->postBeforeCapcth['documento'] = $this->data['cnpj'];

		if ($text_capcth){
			
			$this->postBeforeCapcth['CaptchaInputText'] = $text_capcth;

			$html = $this->execCurl($this->url_capcha, 'POST', $this->postBeforeCapcth);

			$this->text_html = $html;

			$this->html = new DOMDocument();

			$this->html->loadHTML($this->text_html);

			preg_match('~ ICMS a Consumidor Final não Contribuinte de Outra UF - Apuração~', $html, $tagTeste);

			if (isset($tagTeste[0])) {
            	
            	$tagDownload = $tagTeste[0];

            	$this->postBoleto = $this->fillPost($this->postBoleto);

            	foreach ($this->data as $key => $value) {
            		if ($value){
            			$this->postBoleto[$key] = $value;
            		}
            	}

				$this->header = array(
				    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
				    'Accept-Encoding: gzip, deflate, br',
				    'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
				    'Cache-Control: no-cache',
				    'Connection: keep-alive',
				    'Content-Type: application/x-www-form-urlencoded',
				    'Host: www10.fazenda.sp.gov.br',
				    'Origin: https://www10.fazenda.sp.gov.br',
				    'Pragma: no-cache',
				    'Referer: https://www10.fazenda.sp.gov.br/GeradorIntegradoGuias/GeradorGnre/ExibirFormulario',
				    'Upgrade-Insecure-Requests: 1',
				    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36'
				);

				$pdf = $this->execCurl($this->url_post_pdf, 'POST', $this->postBoleto, null, false);

				preg_match('~Geração de Guias de Recolhimento~', $pdf, $isOk);

				if ($isOk){

		            return false;

				} else {

					if(!$this->savePDF($pdf)){

	            		$this->logError('Não foi possivel salvar o XML');

		            	return false;

					} else {

		            	return true;
					}

				}

	        } else {
	            
	            $this->logError('Sessão expirada ou captcha inválido, gere um novo captcha e tente novamente.');

	            return false;
	        }

		} else {
			
			$this->logError('Erro ao resolver captcha');
		}
		

	}

	private function getCaptcha($html){
		
		try{
			$element_captcha = $html->getElementById('CaptchaImage');

			$xpath = new DOMXPath($html); 

			$rowNode = $xpath->query('//a[@href="#CaptchaImage"]')->item(0);

			$idRefresh = $rowNode->getAttribute('id');

			if ($element_captcha){

				$url = $element_captcha->getAttribute('src');

				if ($url){
						
					$url = $this->url_base . $url;

					$path  = realpath(__DIR__ . '/../tpm') . '/' . date('YmdHsi') . '.gif';

					$path = $this->getImageFromUrl($url, $path);

					return $path;

				} else {

					return false;

				}

			} else {
				return false;
			}

		} catch(\Exception $e){

			$this->logError($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());

			return false;
		}
		
	}

	private function getImageFromUrl($url, $path){

		try{

			$ch = curl_init();

			$fp = fopen($path, 'wb');

			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_FILE, $fp);
			
			curl_setopt($ch, CURLOPT_HEADER, 0);

			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
			    'Accept-Encoding: gzip, deflate, br',
			    'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
			    'Cache-Control: no-cache',
			    'Host: www10.fazenda.sp.gov.br',
			    'Pragma: no-cache',
			    'Referer: https://www10.fazenda.sp.gov.br/GeradorIntegradoGuias/GeradorGNRE/pesquisar',
			    'Upgrade-Insecure-Requests: 1',
			    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36'
			));

			curl_setopt($ch, CURLOPT_HEADER, 0);

			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");

			curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt"); //saved cookies

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

			curl_exec($ch);
			
			curl_close($ch);
			
			fclose($fp);
			
			return $path;

		} catch (\Exception $e){

			$this->logError($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());

			return false;
		}
	}

	private function execCurl($url, $method, $data, $certificado = null, $fallowLocation = true){
		
		$httpcode = null;

		$response = null;

		try{

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);

			if ($method == 'POST')
				curl_setopt($ch, CURLOPT_POST, true);

			if ($data)
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

			if ($fallowLocation)
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);

			curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
			
			curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt"); //saved cookies

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	        
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

	        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$response = curl_exec($ch);

			curl_close($ch);

		} catch (\Exception $e){

            throw $e; 
            
		}
		
		return $response;
	}

	private function base64_to_jpeg($base64_string, $output_file) {
	    

	    $ifp = fopen( $output_file, 'wb' ); 

	    // split the string on commas
	    // $data[ 0 ] == "data:image/png;base64"
	    // $data[ 1 ] == <actual base64 string>
	    $data = explode( ',', $base64_string );

	    fwrite( $ifp, base64_decode( $data[ 1 ] ) );

	    fclose( $ifp ); 

	    return $output_file; 
	}

	private function resolveCaptcha($file){
			
		if (!$this->keyCaptch)
			throw new \Exception("É necessário setar key do AntiCaptcha");
			
		$api = new ImageToText();
		
		$api->setVerboseMode(false);

		$api->setKey($this->keyCaptch);

		$api->setFile($file);

		if (!$api->createTask()) {
		    
		    return false;
		}

		$taskId = $api->getTaskId();

		if (!$api->waitForResult()) {
		   
		   return false;
		
		} else {

		    return $api->getTaskSolution();

		}
	}

	private function logError($message){
		return file_put_contents(realpath(__DIR__ . '/../log') . '/' . 'log.txt', date('d/m/Y H:i:s') . ' ' . $message . PHP_EOL, FILE_APPEND);
	}

	private function fillPost ($post){
		
		$xpath = new DomXpath($this->html);

		foreach ($post as $key => $post_value) {

			foreach ($xpath->query('//input[@name="' . $key . '"]') as $rowNode) {
				
				if($rowNode->getAttribute('value'))
			    	$post[$key] = $rowNode->getAttribute('value');
			}
		}

		return $post;
	}
	
	private function savePDF($pdf){
		
		$file = $this->makeRandomString() . '.pdf';

		$folder = realpath(__DIR__ . '/../pdf') . '/';

		$this->filePDF = $folder . $file;

		if ($pdf){
			return file_put_contents($folder . $file, $pdf);
		}

		return false;
	}

	private function makeRandomString($max=6) {
	    
	    $i = 0;
	    
	    $possible_keys = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    
	    $keys_length = strlen($possible_keys);
	    
	    $str = "";
	    
	    while( $i < $max) {
	        
	        $rand = mt_rand(1,$keys_length-1);
	        
	        $str.= $possible_keys[$rand];
	        
	        $i++;
	    }
	    
	    return $str;
	}

	private function clearSessionCurl(){
		unlink('cookie.txt');
	}

	public function copyFilePDF($pathTo){

		try {

			if (is_file($this->filePDF)){
				
				copy($this->filePDF, $pathTo);

				unlink($this->filePDF);

				return $pathTo;

			}

			return false;

		} catch (\Exception $e){

			$this->logError($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());

			return false;
		}

		return false;

	}

	public function setKeyCaptch($key){
		$this->keyCaptch = $key;
	}

	public function getKeyCaptch(){
		return $this->keyCaptch;
	}
}

?>
