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
// * Consultar comprobantes segun tipo
$jsonString= $client->consultaComprobante($SerieNumero, 'factura');
$jsonString= $client->consultaComprobante($SerieNumero, 'boleta');
$jsonString= $client->consultaComprobante($SerieNumero, 'notacredito');
$jsonString= $client->consultaComprobante($SerieNumero, 'notadebito');
$jsonString= $client->consultaComprobante($SerieNumero, 'guiaremitente');
// * Consultar CDR de comprobante en RAW ( informacion en json string)
$jsonString= $client->CDRComprobante($SerieNumero, 'factura');
// * Obtener CDR en ZIP, respuesta  de sunat
$jsonArray = json_decode($jsonString, true); //	array de respuesta $client->CDRComprobante(SerieNumero, TipoComprobante);
$nombreZip = 'CDR-'.$jsonArray['idEmisor'].'-'.$jsonArray['serie'].$jsonArray['numero'].'.zip';
$rawXML	   = base64_decode($jsonArray['xml']);
file_put_contents($nombreZip, $rawXML);
if (file_exists($nombreZip)) {
// * Descomentar para Test de Descargar Zip
/*
 * 
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.basename($nombreZip).'"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($nombreZip));
	readfile($nombreZip);
	exit;
 *
 */
// test read CDRZip get original XML-CDR
	$zip = new ZipArchive;
	if ($zip->open($nombreZip) === TRUE) {
		$cdrXML = $zip->getFromIndex(1);
		$zip->close();
		// Force download XML
		/*
		header('Content-disposition: attachment; filename=CDR.xml');
		header ("Content-Type:text/xml"); 
		print($cdrXML);
		header("Expires: 0");
		exit;
		*/
	} else {
		echo 'failed';
	}
	unlink($nombreZip);
//	exit;
}

//Mostrar respuesta de API en string JSON
//header('Content-Type: application/json');
print($jsonString);
// Convertir respuesta a Array asociativo PHP
$jsonArray = json_decode($jsonString, true);
echo "<pre>";
print_r($jsonArray);
echo "</pre>";
exit;
