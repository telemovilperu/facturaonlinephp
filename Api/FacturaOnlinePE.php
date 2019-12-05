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
	protected $signature;

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
		$this->signature =  hash_hmac("sha256",$this->accessKey.'|'.$timestamp,$this->secretKey);
		return $this;
	}
	public function getSignature(){
		return $this->signature;
	}
	public function getUsuario($timestamp){        
		try {
			$this->setUrl('usuario');
			$client = $this->getClient();			
			$response = $client->request('GET', 'usuario', [
				'headers' => $this->getHeadersAuthorization($timestamp),
				'verify'  => false
			]);
			return $response;
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	public function consultaComprobante($serieNumero, $nombreComprobante, $timestamp){
		$this->setUrl($nombreComprobante.'/'.$serieNumero);
		$client = $this->getClient();
		$response = $client->request('GET', $nombreComprobante.'/'.$serieNumero, [
			'headers' => $this->getHeadersAuthorization($timestamp),
			'verify'  => false
		]);		
		return $response;
	}
	public function CDRComprobante($serieNumero, $nombreComprobante, $timestamp){
		$this->setUrl($nombreComprobante.'/'.$serieNumero.'/constancia');
		$client = $this->getClient();
		$response = $client->request('GET', $nombreComprobante.'/'.$serieNumero.'/constancia', [
			'headers' => $this->getHeadersAuthorization($timestamp),
			'verify'  => false
		]);
		return $response;
	}


	public function getHeadersAuthorization($timestamp){
		if(empty($this->signature)){
			return "signature no generated". PHP_EOL;
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