<?php
require 'vendor/autoload.php';
require_once './Api/FacturaOnlinePE.php';

$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->load();

$client = new \Api\FacturaOnlinePE\Telemovil();
$client->setBaseUrl('https://demoapi2.facturaonline.pe');
$client->setAccessKey($_ENV['MY_ACCESS_KEY']);
$client->setSecretKey($_ENV['MY_SECRET_KEY']);

//  * Nota: el $timestamp, tiempo en unix necesario para generar el Signature utilizado para toda comunicacion con la API-FO
$timestamp = time();
$client->generateSignature($timestamp);
//$client->getSignature();      //  Verificar Signature generado

$jsonString = $client->getUsuario();        //    verificar conexion con API obteniendo datos de la cuenta

$SerieNumero = 'F0011';         //  ConcatenarSerie y numero
// Consultar comprobantes segun tipo
$jsonString= $client->consultaComprobante($SerieNumero, 'factura');
$jsonString= $client->consultaComprobante($SerieNumero, 'boleta');
$jsonString= $client->consultaComprobante($SerieNumero, 'notacredito');
$jsonString= $client->consultaComprobante($SerieNumero, 'notadebito');
$jsonString= $client->consultaComprobante($SerieNumero, 'guiaremitente');
// Consultar CDR de comprobante en RAW ( informacion en json string)
$jsonString= $client->CDRComprobante($SerieNumero, 'factura');
// Obtener CDR en ZIP, respuesta  de sunat
$jsonArray = json_decode($jsonString, true); //	array de respuesta $client->CDRComprobante(SerieNumero, TipoComprobante);
$nombreArhivo = $jsonArray['idEmisor'].'-'.$jsonArray['tipo'].'-'.$jsonArray['serie'].'-'.$jsonArray['numero'].'.zip'; // Sin extension.
$rawXML	   = base64_decode($jsonArray['xml']);
// Preparando lectura de CDRraw a Archivo temporal
$tmpDir = "tmp";
if(!file_exists($tmpDir))
	mkdir($tmpDir, 0777, true);
$tmpFile = $tmpDir.DIRECTORY_SEPARATOR.$nombreArhivo;
file_put_contents($tmpFile, $rawXML);
// Descargas de Archivo CDR, CDR-XML
if (file_exists($tmpFile)) {
// Descomentar para Test de Descargar Zip
	/*
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="CDR-'.basename($nombreArhivo).'"');
	print($rawXML);
	header('Expires: 0');	
	unlink($tmpFile);
	exit;
	*/
// Descomentar para Test Descargar XML-CDR
	/*
	$zip = new ZipArchive;
	if ($zip->open($tmpFile) === TRUE) {
		$cdrXML = $zip->getFromIndex(1);
		$nombreXML = $zip->getNameIndex(1);
		var_dump($cdrXML);
		var_dump($nombreXML);	
		$zip->close();
		header('Content-disposition: attachment; filename="'.$nombreXML.'"');
		header ("Content-Type:text/xml"); 
		print($cdrXML);
		header("Expires: 0");
		unlink($tmpFile);
		exit;
	}
	*/
// Eliminar archivo temporal
	unlink($tmpFile);
}
// Test Exportar comprobantes al formato disponible(xml,pdf,txt) RAW en base64

echo "<pre>";
//Mostrar respuesta de API en string JSON
//header('Content-Type: application/json');
print($jsonString);
// Convertir respuesta a Array asociativo PHP
$jsonArray = json_decode($jsonString, true);
print_r($jsonArray);
echo "</pre>";
exit;
