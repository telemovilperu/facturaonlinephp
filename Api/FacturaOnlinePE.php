<?php
namespace Api\FacturaOnlinePE;
ini_set("error_reporting",E_ALL & ~E_NOTICE);		// por defecto
//ini_set("display_errors",0);						// 1 Activar ver errores | 0 Desactivar ver errores
set_time_limit(0);
class Telemovil{
	private static $instancia;
	protected $baseUrl;
	protected $url;
	protected $client = null;
	protected $accessKey;
	protected $secretKey;
	protected $timestamp;

	public static function getInstance(){
		if(!self::$instancia instanceof self){
			self::$instancia = new self;
		}
		return self::$instancia;
	}
	public function __construct(array $config = ["base_uri" => "https://api2.facturaonline.pe", "url" => "/"]){
		$this->client;
		if (empty($this->baseUrl) && !empty($config['base_uri'])) {
			$this->setBaseUrl($config['base_uri']);
		}
		if (empty($this->url) && !empty($config['url'])) {
			$this->setUrl($config['url']);
		}
		if (is_null($this->client)) {
			$this->getClient();
		}
	}
	public function getClient(array $config = []){
		if (empty($config)) {
			$config = [
				'base_uri' => $this->getBaseUrl(),
				'http_errors' => false,
			];
		}
		if (is_null($this->client)) {
			$this->client = new \GuzzleHttp\Client($config);
		}
		return $this->client;
	}

	public function generateSignature($timestamp){
		if(empty($this->accessKey)){
			return "acessKey no preset". PHP_EOL;
		}
		if(empty($this->secretKey)){
			return "secretKey no preset". PHP_EOL;
		}
		$this->timestamp = $timestamp;
		$this->signature =  hash_hmac("sha256",$this->accessKey.'|'.$this->timestamp,$this->secretKey);
		return $this;
	}
	public function getSignature(){
		return $this->signature;
	}
	public function getUsuario(){
			$this->setUrl('usuario');
			return $this->getRequest('GET', $this->url);
	}
	public function emiteComprobante($jsonData, $nombreComprobante){
		$this->setUrl($nombreComprobante);
		return $this->getRequest('POST', $this->url, $jsonData);
	}
	public function consultaComprobante($serieNumero, $nombreComprobante){
		$this->setUrl($nombreComprobante.'/'.$serieNumero);
		return $this->getRequest('GET', $this->url);
	}
	public function exportaComprobante($serieNumero, $nombreComprobante, $tipo='pdf'){
		if($tipo = 'txt') $tipo = 'plain';
		$this->setUrl($nombreComprobante.'/'.$serieNumero.'/exportar?tipo='.$tipo);
		return $this->getRequest('GET', $this->url);
	}
	public function CDRComprobante($serieNumero, $nombreComprobante){
		$this->setUrl($nombreComprobante.'/'.$serieNumero.'/constancia');
		$jsonString = $this->getRequest('GET', $this->url);
		$jsonArray  = json_decode($jsonString, true);
		$bufferXML = $jsonArray['xml'];
		unset($jsonArray['xml']);
		$stringXML = json_encode($bufferXML['data']);
		$rawXML = explode(',',trim($stringXML,'[]'));
		$dataXML = implode('', array_map('chr',$rawXML));
		$jsonArray = array_merge($jsonArray, array ('xml' => $dataXML));
		return json_encode($jsonArray);
	}
	public function getRequest($method, $url, $body=''){
		print $body;
		try {
			$client = $this->getClient();
			$response = $client->request($method, $url, [
				'headers'	=> $this->getHeadersAuthorization($this->timestamp),
				'verify'	=> false,
				'json'		=> $body
			]);
			//echo $client->getUrl();
			$code = $response->getStatusCode(); // 200  *http codes : https://developer.mozilla.org/es/docs/Web/HTTP/Status
			//echo $code;
			return $response->getBody();
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	public function getHeadersAuthorization($timestamp){
		if(empty($timestamp)){
			$timestamp = time();
			$this->generateSignature($timestamp);
		}
		$Authorization = 'Fo '.$this->accessKey.':'.$this->signature.':'.$timestamp;;
		return [
			'Accept'        => 'application/json',
			'Authorization' => $Authorization
		];
	}

	public function setBaseUrl($baseUrl){
		$this->baseUrl = $baseUrl;
		unset($this->client);
		return $this;
	}
	public function getBaseUrl(){
		return $this->baseUrl;
	}
	public function setUrl($url){
		$this->url = $url;
		return $this;
	}
	public function getUrl(){
		return $this->url;
	}
	public function setAccessKey($accessKey){
		$this->accessKey = $accessKey;
		return $this;
	}
	public function getAccessKey(){
		return $this->accessKey;
	}
	public function setSecretKey($secretKey){
		$this->secretKey = $secretKey;
		return $this;
	}
	public function getSecretKey(){
		return $this->secretKey;
	}
}