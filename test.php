<?php
require 'vendor/autoload.php';
require_once './Api/FacturaOnlinePE.php';

$client = new \Api\FacturaOnlinePE\Telemovil();

$client->setBaseUrl('https://demoapi2.facturaonline.pe');
$client->setAccessKey('MY_ACCESS_KEY');
$client->setSecretKey('MY_SECRET_KEY');

$timestamp = time();
$client->generateSignature($timestamp);
$client->getSignature();

$response = $client->getUsuario($timestamp);
$response= $client->consultaComprobante('F0011', 'factura', $timestamp);
$response= $client->consultaComprobante('B0011', 'boleta', $timestamp);
$response= $client->consultaComprobante('BC011', 'notacredito', $timestamp);
$response= $client->consultaComprobante('FD011', 'notadebito', $timestamp);
$response= $client->consultaComprobante('T0011', 'guiaremitente', $timestamp);


$response= $client->CDRComprobante('F0011', 'factura', $timestamp);

//header('Content-Type: application/json');
//echo $client->getUrl();
$code = $response->getStatusCode(); // 200  *http codes : https://developer.mozilla.org/es/docs/Web/HTTP/Status
// echo $code;
//echo $response->getBody();

