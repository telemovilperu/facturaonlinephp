<?php
require 'vendor/autoload.php';
require_once './Api/FacturaOnlinePE.php';

$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->load();

$client = new \Api\FacturaOnlinePE\Telemovil();
$client->setBaseUrl('https://demoapi2.facturaonline.pe');
$client->setAccessKey($_ENV['MY_ACCESS_KEY']);
$client->setSecretKey($_ENV['MY_SECRET_KEY']);

$timestamp = time();
$client->generateSignature($timestamp);

$client->getSignature();      //  Verificar Signature generado

//  * Nota: mantener el $timestamp con el que se genero el Signature para toda comunicacion con la API
$response = $client->getUsuario($timestamp);        //    verificar conexion con API obteniendo datos de la cuenta

$SerieNumero = 'F0011';         //  ConcatenarSerie y numero
// * Consultar comprobantes segun tipo
$response= $client->consultaComprobante($SerieNumero, 'factura', $timestamp);
$response= $client->consultaComprobante($SerieNumero, 'boleta', $timestamp);
$response= $client->consultaComprobante($SerieNumero, 'notacredito', $timestamp);
$response= $client->consultaComprobante($SerieNumero, 'notadebito', $timestamp);
$response= $client->consultaComprobante($SerieNumero, 'guiaremitente', $timestamp);

// * Consultar CDR de comprobante en RAW ( informacion en json string)
$response= $client->CDRComprobante($SerieNumero, 'factura', $timestamp);

//Mostrar respuesta de API en string o array
$jsonString = $response->getBody();
$jsonArray  = json_decode($jsonString, true);
//var_dump($jsonArray);

//echo $client->getUrl();
$code = $response->getStatusCode(); // 200  *http codes : https://developer.mozilla.org/es/docs/Web/HTTP/Status
//echo $code;

// test get CDRZip
$json = $response->getBody();
$obj = json_decode($json);
$xml = $obj->xml->data;
$xml = json_encode($xml);
$data = trim($xml,'[]');
$data = explode(',',$data);
$data = array_map('chr',$data);
$xml = implode('',$data);
$decoded = base64_decode($xml);
$file = 'CDR.zip';
file_put_contents($file, $decoded);
if (file_exists($file)) {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.basename($file).'"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));
	readfile($file);
	exit;
}
exit; 
