<?php
require 'vendor/autoload.php';
require_once './Api/FacturaOnlinePE.php';

$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->load();

$client = new \Api\FacturaOnlinePE\Telemovil();
$client->setBaseUrl('https://demoapi2.facturaonline.pe');
$client->setAccessKey($_ENV['MY_ACCESS_KEY']);
$client->setSecretKey($_ENV['MY_SECRET_KEY']);

//  * Nota: el $timestamp, tiempo en unix necesario para generar el Signature utilizado para toda comunicación con la API-FO
$timestamp = time();
$client->generateSignature($timestamp);
//$client->getSignature();      //  Verificar Signature generado

$jsonString = $client->getUsuario();        //    verificar conexión con API obteniendo datos de la cuenta

$SerieNumero = 'F0011';         //  Concatenar Serie y Número
// Consultar comprobantes segun tipo
$jsonString= $client->consultaComprobante($SerieNumero, 'factura');
$jsonString= $client->consultaComprobante($SerieNumero, 'boleta');
$jsonString= $client->consultaComprobante($SerieNumero, 'notacredito');
$jsonString= $client->consultaComprobante($SerieNumero, 'notadebito');
$jsonString= $client->consultaComprobante($SerieNumero, 'guiaremitente');
// Consultar CDR de comprobante en RAW ( informacion en json string)
$jsonString= $client->CDRComprobante($SerieNumero, 'factura');
// Obtener CDR en ZIP, respuesta  de sunat
$jsonArray = json_decode($jsonString, true); //	array de respuesta $client->CDRComprobante(SerieNumero, tipoComprobante);
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
// Exportar comprobantes al formato disponible(xml,pdf,txt) RAW segun tipo, defecto: tipo=pdf
$tipo = 'pdf';
$rawComprobante= $client->exportaComprobante($SerieNumero, 'factura', $tipo);
// Descomentar para Test de Descarga Exportar
	/*
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="CPE-'.basename($SerieNumero).'.'.$Tipo.'"');
	print($rawComprobante);
	header('Expires: 0');
	exit;
	*/

// emisión de comprobante
//	* Generar json Data segun rubro de negocio con los valores indicados en la documentación 
$stringData = '
{
	"tipoOperacion": "0101",
	"serie": "FL01",
	"numero": 1,
	"fechaEmision": "2019-12-18 15:15:18",
	"tipoMoneda": "PEN",
	"items": [{
		"unidadMedidaCantidad": "BX",
		"cantidad": 2,
		"codigoProductoSunat": "42132205",
		"codigoProductoGs1": "1234567890123",
		"descripcion": "PRODUCTO GRAVADO",
		"valorUnitario": 10,
		"precioVentaUnitario": 11.8,
		"tipoPrecioVentaUnitario": "01",
		"montoTotalImpuestosItem": 3.6,
		"baseAfectacionIgv": 20,
		"montoAfectacionIgv": 3.6,
		"porcentajeImpuesto": 18,
		"tipoAfectacionIgv": "10",
		"codigoTributo": "1000",
		"valorVenta": 20,
		"adicional": {}
	}],
	"montoTotalImpuestos": 3.6,
	"totalVentaGravada": 20,
	"sumatoriaIgv": 3.6,
	"importeTotal": 23.6,
	"adicional": {
		"fechaVencimiento": "2019-12-31",
		"codigoSunatEstablecimiento": "0001",
		"ordenCompra": "102566852",
		"formaPago": "009"
	},
	"receptor": {
		"tipo": "6",
		"nro": "10450961219"
	}
}
';
$arrayData = json_decode($stringData, true); // convertir string a  objeto array
//	* Si data es fetchrow de DB almacenar en array asociativo respetando los nombre segun documentacion, como ejemplo linea anterior con print_r
$jsonString= $client->emiteComprobante($arrayData, 'factura');
//Mostrar respuesta de API en string JSON
//header('Content-Type: application/json');
echo "<pre>";
print($jsonString);
// Convertir respuesta a Array asociativo PHP
$jsonArray = json_decode($jsonString, true);
print_r($jsonArray);
echo "</pre>";