<?php

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrModel
{
    public function generateQr(string $data, string $pathToSave): string
    {
        // Crear el QR
        $qr = QrCode::create($data)
            ->setSize(300)
            ->setMargin(10);

        // Writer
        $writer = new PngWriter();

        // Construir la imagen final
        $result = $writer->write($qr);

        // Guardar archivo
        $result->saveToFile($pathToSave);

        // Devolver base64
        return $result->getDataUri();
    }
}