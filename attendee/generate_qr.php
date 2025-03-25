<?php
require '../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Example data for the QR code
$data = "Event ID: 123, User ID: 456";

// Create QR code
$qrCode = new QrCode($data);
$writer = new PngWriter();
$result = $writer->write($qrCode);

// Save QR code to file
$qrCodePath = '../qrcodes/ticket_123_456.png';
$result->saveToFile($qrCodePath);

echo "QR code generated: <img src='$qrCodePath'>";
?>