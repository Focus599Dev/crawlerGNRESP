<?php 
 
namespace Focus599Dev\CrawlerSP;

use DOMDocument;
use DomXpath;

class crawlerDARE{

	protected $url_base = 'https://www4.fazenda.sp.gov.br/DareICMS/DareAvulso';

	protected $text_html = '';

	protected $html;

	protected $data = array();

	protected $filePDF;

	protected $file_cookiee;

	protected $post = array();

	protected $header = array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
		'Accept-Encoding: gzip, deflate, br',
		'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
		'Cache-Control: no-cache',
		'Connection: keep-alive',
		'Cookie: _ga=GA1.1.707561628.1641233437; _ga_7RC6MLS8YN=GS1.1.1645036954.4.0.1645036956.0',
		'Host: www4.fazenda.sp.gov.br',
		'Pragma: no-cache',
		'Referer: https://portal.fazenda.sp.gov.br/',
		'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="98", "Google Chrome";v="98"',
		'sec-ch-ua-mobile: ?0',
		'sec-ch-ua-platform: "macOS"',
		'Sec-Fetch-Dest: document',
		'Sec-Fetch-Mode: navigate',
		'Sec-Fetch-Site: same-site',
		'Sec-Fetch-User: ?1',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36'
	);

	function __construct($data){

		set_time_limit(0);

		error_reporting(1);

		$this->clearSessionCurl();

		$this->data = $data;

		$this->file_cookiee = realpath(__DIR__ . '/../tpm/') . $this->makeRandomString(10) . '.txt';

		if (session_status() == PHP_SESSION_NONE)
            session_start();
	}

	public function getBoleto(){

		$html = $this->execCurl($this->url_base, 'GET', null);

		$this->text_html = $html;

		$this->header = array(
			'Accept: */*',
			'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
			'Cache-Control: no-cache',
			'Connection: keep-alive',
			'Content-Length: 0',
			'Content-Type: application/json; charset=utf-8',
			'Host: www4.fazenda.sp.gov.br',
			'Origin: https://www4.fazenda.sp.gov.br',
			'Pragma: no-cache',
			'Referer: https://www4.fazenda.sp.gov.br/DareICMS/DareAvulso',
			'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="98", "Google Chrome";v="98"',
			'sec-ch-ua-mobile: ?0',
			'sec-ch-ua-platform: "macOS"',
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36',
			'X-Requested-With: XMLHttpRequest'
		);

		$dataCNPJ = $this->execCurl($this->url_base . '/btnConsultar_Click/' . $this->data['cnpj'], 'POST', array());	
		
		// if ($dataCNPJ){

			// $dataCNPJ = json_decode($dataCNPJ);
			$dataCNPJ = new \stdClass();

			if (!$dataCNPJ->inscricaoEstadual){

				$dataCNPJ->inscricaoEstadual = $this->data['incricao_estadual'];

				$dataCNPJ->cpf = $this->data['cnpj'];

				$dataCNPJ->cnpj = $this->data['cnpj'];

				$dataCNPJ->endereco = $this->data['endereco'];
				
				$dataCNPJ->razaoSocial = $this->data['razao'];
				
				$dataCNPJ->telefone = $this->data['telefone'];
				
				$dataCNPJ->cidade = $this->data['municipio'];
				
				$dataCNPJ->uf = $this->data['uf_emp'];

				$dataCNPJ->cpr = '0000';
			}

			$postImpostos = array(
				'cpr' => $dataCNPJ->cpr,
				'receita' => array(
					'codigoServicoDARE' => (Int)$this->data['receita'],
				),
				'referencia' => (String)$this->data['referencia'],
				'dataVencimento' => trim($this->data['vencimento']),
				'valor' => (Float)$this->data['valor'],
			);

			$postImpostos = json_encode($postImpostos);

			$postImpostos = str_replace('\/', '/', $postImpostos);

			$this->header = array(
				'Accept: */*',
				'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
				'Cache-Control: no-cache',
				'Connection: keep-alive',
				'Content-Length: ' . strlen($postImpostos),
				'Content-Type: application/json; charset=utf-8',
				'Host: www4.fazenda.sp.gov.br',
				'Origin: https://www4.fazenda.sp.gov.br',
				'Pragma: no-cache',
				'Referer: https://www4.fazenda.sp.gov.br/DareICMS/DareAvulso',
				'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="98", "Google Chrome";v="98"',
				'sec-ch-ua-mobile: ?0',
				'sec-ch-ua-platform: "macOS"',
				'Sec-Fetch-Dest: empty',
				'Sec-Fetch-Mode: cors',
				'Sec-Fetch-Site: same-origin',
				'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36',
				'X-Requested-With: XMLHttpRequest'
			);

			$dataImpostos = $this->execCurl($this->url_base . '/btnCalcular_Click/', 'POST', $postImpostos);	

			$dataImpostos = json_decode($dataImpostos);

			if ($dataImpostos->erro->estaOk){

				$postPDF = array(
					"inscricaoEstadual" => $dataCNPJ->inscricaoEstadual,
					"cnpj" => $dataCNPJ->cnpj,
					"cpf" => $dataCNPJ->cnpj,
					"razaoSocial" => preg_replace('/([^A-Za-z0-9\s])*/', '', $dataCNPJ->razaoSocial),
					"telefone" => $dataCNPJ->telefone,
					"endereco" => $dataCNPJ->endereco,
					"cidade" => $dataCNPJ->cidade,
					"uf" => $dataCNPJ->uf,
					"cpr" => (String)$dataCNPJ->cpr,
					"referencia" => (String)$this->data['referencia'],
					"dataVencimento" => trim($this->data['vencimento']),
					"Receita" => array(
						"codigoServicoDARE" => (Int)$this->data['receita'],
						"CamposEspecificos" => array(
							array("valor" => ""),
							array("valor" => ""),
							array("valor" => ""),
						),
					),
					"observacao" => $this->data['complementar'],
					"valor" => $dataImpostos->valor,
					"valorJuros" => round($dataImpostos->valorJuros, 2),
					"valorMulta" => round($dataImpostos->valorMulta, 2),
					"valorTotal" => round($dataImpostos->valorTotal, 2),
				);

				$postPDF = json_encode($postPDF);

				$postPDF = str_replace('\/', '/', $postPDF);

				$this->header = array(
					'Accept: */*',
					'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
					'Cache-Control: no-cache',
					'Connection: keep-alive',
					'Content-Length: ' . strlen($postPDF),
					'Content-Type: application/json; charset=utf-8',
					'Host: www4.fazenda.sp.gov.br',
					'Origin: https://www4.fazenda.sp.gov.br',
					'Pragma: no-cache',
					'Referer: https://www4.fazenda.sp.gov.br/DareICMS/DareAvulso',
					'sec-ch-ua: " Not A;Brand";v="99", "Chromium";v="98", "Google Chrome";v="98"',
					'sec-ch-ua-mobile: ?0',
					'sec-ch-ua-platform: "macOS"',
					'Sec-Fetch-Dest: empty',
					'Sec-Fetch-Mode: cors',
					'Sec-Fetch-Site: same-origin',
					'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.80 Safari/537.36',
					'X-Requested-With: XMLHttpRequest'
				);

				$dataPDF = $this->execCurl($this->url_base . '/btnGerar_Click/', 'POST', $postPDF);	

				$dataPDF = json_decode($dataPDF);

				$this->data = $dataPDF;

				if (isset($dataPDF->documentoImpressao)){

					$pdf = base64_decode($dataPDF->documentoImpressao);

					if (preg_match('/%PDF-1(\.[0-9])?/', $pdf)){

						if(!$this->savePDF($pdf)){
			
							$this->logError('Não foi possivel salvar o PDF');
			
							return false;
			
						} else {
			
							return true;
						}
			
					}
			
					return false;
			
				}
			}
		// }

		return false;
	}

	private function clearSessionCurl(){
		unlink($this->file_cookiee);
	}

	private function savePDF($pdf){

		$file = $this->makeRandomString() . '.pdf';

		$folder = realpath(__DIR__ . '/../../../../public/tmp') . '/';

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

	private function logError($message){
		return file_put_contents(realpath(__DIR__ . '/../log') . '/' . 'log.txt', date('d/m/Y H:i:s') . ' ' . $message . PHP_EOL, FILE_APPEND);
	}

	private function execCurl($url, $method, $data, $certificado = null, $fallowLocation = true){
		
		$httpcode = null;

		$response = null;

		try{

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);

			if ($method == 'POST')
				curl_setopt($ch, CURLOPT_POST, true);

			if ($data && is_array($data))
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			else if ($data && is_string($data))
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

			if ($fallowLocation)
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);

			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->file_cookiee);
			
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->file_cookiee); //saved cookies

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	        
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	        
			curl_setopt($ch, CURLOPT_HEADER, 0);

	        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$response = curl_exec($ch);

			curl_close($ch);

		} catch (\Exception $e){

            throw $e; 
            
		}
		
		return $response;
	}

	public function getData(){
		return $this->data;
	}
}
