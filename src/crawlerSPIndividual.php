<?php 

namespace Focus599Dev\CrawlerSP;

use DOMDocument;
use DomXpath;

class crawlerSPIndividual{

	protected $url_base = 'https://www.fazenda.sp.gov.br/guiasinternet/Gare/Paginas/Gare.aspx';

	protected $url_boleto = 'https://www.fazenda.sp.gov.br/guiasinternet/Gare/Paginas/guias/Gnre.pdf?hora=';

	protected $text_html = '';

	protected $html;

	protected $data = array();

	protected $filePDF;

	protected $file_cookiee;

	protected $post = array(
		'__EVENTTARGET' => '',
		'__EVENTARGUMENT' => '',
		'__LASTFOCUS' => '',
		'__VIEWSTATE' => '',
		'__VIEWSTATEGENERATOR' => '',
		'__EVENTVALIDATION' => '',
		'ReceitaTipo' => '',
	);

	protected $header = array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
		'Accept-Language: en-US,en;q=0.9,pt;q=0.8',
		'Cache-Control: no-cache',
		'Connection: keep-alive',
		'Host: www.fazenda.sp.gov.br',
		'Pragma: no-cache',
		'sec-ch-ua: " Not;A Brand";v="99", "Google Chrome";v="97", "Chromium";v="97"',
		'sec-ch-ua-mobile: ?0',
		'sec-ch-ua-platform: "macOS"',
		'Sec-Fetch-Dest: document',
		'Sec-Fetch-Mode: navigate',
		'Sec-Fetch-Site: none',
		'Sec-Fetch-User: ?1',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36'
	);

	function __construct($data){

		set_time_limit(0);

		error_reporting(1);

		$this->clearSessionCurl();

		$this->data = $data;

		$this->file_cookiee = $this->generateRandomString(10) . '.txt';

		if (session_status() == PHP_SESSION_NONE)
            session_start();
	}

	public function getBoleto(){

		$html = $this->execCurl($this->url_base, 'GET', null);

		$this->text_html = $html;

		$this->html = new DOMDocument();

		$this->html->loadHTML($this->text_html);

		$this->post = $this->fillPost($this->post);

		$this->post['ReceitaTipo'] = 'GNRE';

		$this->post['__EVENTTARGET'] = 'ReceitaTipo';

		$html = $this->execCurl($this->url_base, 'POST', $this->post);

		$this->text_html = $html;

		$this->html = new DOMDocument();

		$this->html->loadHTML($this->text_html);

		$this->post = $this->fillPost($this->post);

		$this->post['CodigoReceita'] = '1|' . $this->data['receita'] . '|3';

		$this->post['__EVENTTARGET'] = 'CodigoReceita';

		$html = $this->execCurl($this->url_base, 'POST', $this->post);

		$this->text_html = $html;

		$this->html = new DOMDocument();

		$this->html->loadHTML($this->text_html);

		$this->post = $this->fillPost($this->post);

		$this->text_html = $html;

		$this->html = new DOMDocument();

		$this->html->loadHTML($this->text_html);

		$this->post['CnpjCpf'] = $this->data['cnpj'];

		$this->post['btnBuscaDados'] = 'Processa';
		
		$this->post['rblGrava'] = 'Sim';

		$html = $this->execCurl($this->url_base, 'POST', $this->post);

		$this->text_html = $html;

		$this->html = new DOMDocument();

		$this->html->loadHTML($this->text_html);

		$this->post['__EVENTTARGET'] = '';

		$this->post['btnGerarGARE'] = 'Gerar Guia';

		$this->post['UFFavorecida'] = '';
		
		$this->post['NomeContribuinte'] = '';
		
		$this->post['Endereco'] = '';
		
		$this->post['Municipio'] = '';
		
		$this->post['UF'] = '';
		
		$this->post['Cep'] = '';
		
		$this->post['Telefone'] = '';
		
		$this->post['InscricaoEstadual'] = '';
		
		$this->post['Observacoes'] = '';
		
		$this->post['Convenio'] = '';
		
		$this->post['DataVencimento'] = '';
		
		$this->post['Referencia'] = '';
		
		$this->post['ValorOriginal'] = '';
		
		$this->post['Juros'] = '';
		
		$this->post['Multa'] = '';
		
		$this->post['AcrescimosFinanceiros'] = '';

		$this->post = $this->fillPost($this->post);

		$this->post['Observacoes'] = $this->data['complementar'];
		
		$this->post['DataVencimento'] = $this->data['vencimento'];
		
		$this->post['Referencia'] = $this->data['referencia'];
		
		$this->post['ValorOriginal'] = $this->data['valor'];
		
		$this->post['UF'] = $this->data['uf'];
		
		$this->post['NomeContribuinte'] = $this->data['razao'];
		
		$this->post['Municipio'] = $this->data['municipio'];

		$html = $this->execCurl($this->url_base, 'POST', $this->post);

		$this->text_html = $html;

		$this->html = new DOMDocument();

		$this->html->loadHTML($this->text_html);

		$url = $this->url_boleto . $this->DataHoraFim(date('H'), date('m'), date('s'));

		$pdf = $this->execCurl($this->url_boleto, 'GET', null);

		if (preg_match('/%PDF-1.4/', $pdf)){

			if(!$this->savePDF($pdf)){

				$this->logError('Não foi possivel salvar o PDF');

				return false;

			} else {

				return true;
			}

		}

		return false;

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

			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->file_cookiee);
			
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->file_cookiee); //saved cookies

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	        
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

	        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$response = curl_exec($ch);

			curl_close($ch);

		} catch (\Exception $e){

            throw $e; 
            
		}
		
		return $response;
	}

	private function logError($message){
		return file_put_contents(realpath(__DIR__ . '/../log') . '/' . 'log.txt', date('d/m/Y H:i:s') . ' ' . $message . PHP_EOL, FILE_APPEND);
	}

	private function fillPost ($post){
		
		$xpath = new DomXpath($this->html);

		foreach ($post as $key => $post_value) {
			
			foreach ($xpath->query('//input[@name="' . $key . '"]') as $rowNode) {

				if($rowNode->getAttribute('value')){
					$post[$key] = $rowNode->getAttribute('value');
				}

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
		unlink($this->file_cookiee);
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

	public function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	private function DataHoraFim($h, $m, $s){
		
		$oneSecond = 1000;
		
		$oneMinute = 60 * $oneSecond;
		
		$oneHour = 60 * $oneMinute;
		
		$dataHoraInvertida = ''; 

		$dataInvertidaDia = ''; 

		$dataInvertidaMes = ''; 

		$dataInvertidaAno = ''; 

		$dataInvertidaHora = ''; 

		$dataInvertidaMinuto = '';

		$dataInvertidaSegundo = '';
		
		$dataAtual = new \DateTime();
		
		$dataInMS = time();

		$dataInMS = $dataInMS + $oneHour * $h + $oneMinute * $m + $oneSecond * $s;
		
		$dataAtual->setTimestamp($dataInMS);
		
		$dataInvertidaDia = $this->FormataDataHora($dataAtual->format('d'));
		
		$dataInvertidaMes = $this->FormataDataHora($dataAtual->format('m'));
		
		$dataInvertidaAno = $dataAtual->format('y');
		
		$dataInvertidaHora = $this->FormataDataHora($dataAtual->format('H'));
		
		$dataInvertidaMinuto = $this->FormataDataHora($dataAtual->format('i'));
		
		$dataInvertidaSegundo = $this->FormataDataHora($dataAtual->format('s'));
		
		$dataHoraInvertida = $dataInvertidaAno . $dataInvertidaMes . $dataInvertidaDia . $dataInvertidaHora . $dataInvertidaMinuto . $dataInvertidaSegundo;
		
		return $dataHoraInvertida;
	}

	//-------------------------------

	private function FormataDataHora($x){
		
		$ValorF = '0' . $x;

		$ValorF = substr($ValorF, strlen($ValorF) -2 , 2);

		return $ValorF;
	}
}

?>
