<?php

/**
 * Título: Proceso de Carga y Generación de PDF con Imágenes
 * Descripción: Este script maneja la subida segura de imágenes (logo y código de barras)
 * para generar un archivo PDF utilizando las librerías FPDF y GD.
 * Autor: Oscar Gonzalez
 * Versión: 1.1
 * Fecha: 2025-05-05
 *
 * Mejoras de Seguridad Aplicadas:
 * - Validación estricta del tipo de archivo (MIME) con `finfo_file()`.
 * - Conversión de imágenes para neutralizar código malicioso incrustado.
 * - Generación de nombres de archivo únicos para prevenir colisiones y sobrescritura.
 * - Manejo seguro de directorios y permisos.
 * - Registro de errores (`error_log`) para auditoría.
 * - Limpieza de archivos temporales.
 */

// Incluir la librería FPDF.
require('fpdf/fpdf.php');

// Directorio seguro para almacenar las cargas.
// Recomendación: Asegurar que este directorio no sea accesible públicamente a través del navegador.
$uploadDir = __DIR__ . '/temp_uploads/';

// Crear el directorio si no existe y establecer permisos.
// Usar un modo de permisos seguro (0755). Se recomienda 0750 o 0700 si es posible para mayor seguridad.
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        // Gestión de errores: Si la creación del directorio falla.
        error_log("Error: No se pudo crear el directorio de carga: " . $uploadDir);
        exit("Error interno del servidor. Por favor, inténtelo de nuevo más tarde.");
    }
}

// Tipos MIME permitidos.
// Se aceptan imágenes comunes que pueden ser procesadas por la librería GD.
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Valida, convierte, sube y retorna la ruta segura de un archivo de imagen.
 *
 * @param string $fileInputName El nombre del campo de archivo del formulario.
 * @param string $targetDir El directorio de destino para la carga.
 * @param array $allowedMimes Un array de tipos MIME permitidos.
 * @return string|false La ruta completa del archivo subido o false si falla.
 */
function processUpload($fileInputName, $targetDir, $allowedMimes)
{
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] != UPLOAD_ERR_OK) {
        // Error de subida o archivo no proporcionado.
        return false;
    }

    $tmpName = $_FILES[$fileInputName]['tmp_name'];
    $fileSize = $_FILES[$fileInputName]['size'];
    $maxSize = 2 * 1024 * 1024; // Límite de 2 MB en bytes para prevenir sobrecarga del servidor.

    // **Validación de tamaño:** Verifica que el archivo no exceda el límite.
    if ($fileSize > $maxSize) {
        error_log("Error de carga: El archivo es demasiado grande. Tamaño: " . $fileSize . " bytes.");
        // Se retorna false para evitar exponer detalles sensibles al usuario.
        return false;
    }

    // **Validación estricta del tipo MIME:** Usa `finfo` para obtener el tipo MIME real.
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        error_log("Error: No se pudo inicializar finfo.");
        return false;
    }
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        error_log("Error de carga: Tipo de archivo no permitido. MIME: " . $mimeType);
        return false;
    }

    // **Neutralización de amenazas:** Procesar la imagen con la librería GD para eliminar metadatos y código malicioso.
    $img = null;
    try {
        if ($mimeType == 'image/jpeg') {
            $img = imagecreatefromjpeg($tmpName);
        } elseif ($mimeType == 'image/png') {
            $img = imagecreatefrompng($tmpName);
        } elseif ($mimeType == 'image/webp') {
            $img = imagecreatefromwebp($tmpName);
        }

        if (!$img) {
            error_log("Error al procesar la imagen con GD. MIME: " . $mimeType);
            return false;
        }

        // **Generación de nombre seguro:** Se usa `uniqid` para evitar sobrescritura de archivos.
        $safeFileName = uniqid('', true) . '.png';
        $uploadPath = rtrim($targetDir, '/') . '/' . $safeFileName;

        // **Validación de la ruta de destino:** Evita ataques de "directory traversal".
        if (!is_dir(pathinfo($uploadPath, PATHINFO_DIRNAME))) {
            error_log("Error de seguridad: Directorio de destino no válido.");
            return false;
        }

        // Guardar la imagen procesada como un PNG para asegurar la compatibilidad con FPDF.
        if (!imagepng($img, $uploadPath)) {
            error_log("Error: No se pudo guardar la imagen procesada en: " . $uploadPath);
            return false;
        }

        imagedestroy($img);

        return $uploadPath;
    } catch (Exception $e) {
        // Capturar y registrar cualquier excepción inesperada.
        error_log("Excepción durante el procesamiento de la imagen: " . $e->getMessage());
        return false;
    }
}

// Procesar los archivos subidos.
$logoPath = processUpload('logo', $uploadDir, $allowedMimes);
$barcodePath = processUpload('barcode', $uploadDir, $allowedMimes);

// Si el logo es requerido y no se pudo subir, detener la ejecución.
if ($logoPath === false) {
    exit("Error: El logo es un archivo requerido y no pudo ser procesado.");
}

// **Sanitización de entrada:** Se utiliza un cast a `(int)` para asegurar que el valor sea un entero.
$withBarcode = isset($_POST['withBarcode']) ? (int)$_POST['withBarcode'] : 0;

// Crear PDF
$pdf = new FPDF('P', 'mm', [90, 55]);
$pdf->SetMargins(0, 0, 0);
$pdf->AddPage();

// **Validación de existencia de archivo:** Se verifica que el archivo existe antes de intentar usarlo.
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 0, 0, 55);
} else {
    error_log("Error: El archivo del logo no existe en la ruta: " . $logoPath);
}

$pdf->Ln(23);

// TELÉFONO
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', '', 14);
$pdf->Cell(0, 5, "3016388895", 0, 1, 'C', true);

// REGALO
$pdf->SetTextColor(184, 134, 0);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 6, "REGALO", 0, 1, 'C');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 6, "$30.000", 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 4, "mystock.com.co", 0, 1, 'C');

// ESPACIO CÓDIGO DE BARRAS
$pdf->Ln(8);

// **Validación de existencia de archivo:** Se verifica la existencia del archivo del código de barras.
if ($withBarcode && $barcodePath && file_exists($barcodePath)) {
    $pdf->Image($logoPath, 0, 50, 55);
    $pdf->Image($barcodePath, 0, 55, 55);
} else {
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 20, "ESPACIO PARA CODIGO DE BARRAS", 1, 1, 'C');
}

// **Validación de existencia de la imagen estática.**

$staticImagePath = "./images/logo.jpeg";
if (file_exists($staticImagePath)) {
    $pdf->Image($staticImagePath, 23, 45.2, 9);
} else {
    error_log("Error: La imagen estática del logo no se encuentra en la ruta: " . $staticImagePath);
}

// Descargar PDF
$filename = $withBarcode ? "ticket-con-codigo.pdf" : "ticket-sin-codigo.pdf";
$pdf->Output("D", $filename);

// **Limpieza segura de archivos temporales:** Se eliminan los archivos subidos solo si existen.
if (file_exists($logoPath)) {
    unlink($logoPath);
}
if ($barcodePath && file_exists($barcodePath)) {
    unlink($barcodePath);
}
