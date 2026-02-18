<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Storage\StorageClient;

/**
 * 🖼️ Servicio de subida y compresión de imágenes
 * Maneja toda la lógica de compresión nativa con PHP GD
 * y subida a Google Cloud Storage
 */
class ImageUploadService
{
    protected $storage;
    protected $bucket;

    public function __construct()
    {
        $this->storage = new StorageClient([
            'projectId' => config('services.google.project_id'),
            'keyFilePath' => storage_path('app/' . config('services.google.keyfile')),
        ]);

        $this->bucket = $this->storage->bucket(config('services.google.storage_bucket'));
    }

    /**
     * 📤 Validar tamaño de imagen (máximo 6MB)
     */
    public function validarTamano($file)
    {
        $maxSizeBytes = 6 * 1024 * 1024; // 6MB
        if ($file->getSize() > $maxSizeBytes) {
            $sizeMB = round($file->getSize() / (1024 * 1024), 2);
            return [
                'valid' => false,
                'error' => "Imagen demasiado grande: {$sizeMB}MB. Máximo permitido: 6MB"
            ];
        }
        return ['valid' => true];
    }

    /**
     * 🖼️ Validar tipo de archivo
     */
    public function validarTipo($file)
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Tipo de archivo no permitido. Solo: JPEG, PNG, WebP'
            ];
        }
        return ['valid' => true];
    }

    /**
     * 🎨 Comprimir imagen nativa con PHP GD
     * Retorna datos comprimidos o null en caso de error
     */
    private function comprimirImagenNativa($tempPath, $imageInfo)
    {
        try {
            // Crear imagen desde archivo según tipo
            switch ($imageInfo['mime']) {
                case 'image/jpeg':
                    $sourceImage = \imagecreatefromjpeg($tempPath);
                    break;
                case 'image/png':
                    $sourceImage = \imagecreatefrompng($tempPath);
                    break;
                case 'image/gif':
                    $sourceImage = \imagecreatefromgif($tempPath);
                    break;
                case 'image/webp':
                    $sourceImage = \imagecreatefromwebp($tempPath);
                    break;
                default:
                    throw new \Exception("Tipo de imagen no soportado: " . $imageInfo['mime']);
            }

            if (!$sourceImage) {
                throw new \Exception("No se pudo crear la imagen desde el archivo");
            }

            // Obtener dimensiones originales
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calcular nuevas dimensiones (máximo 800px)
            $maxDimension = 800;
            if ($originalWidth > $maxDimension || $originalHeight > $maxDimension) {
                if ($originalWidth > $originalHeight) {
                    $newWidth = $maxDimension;
                    $newHeight = ($originalHeight * $maxDimension) / $originalWidth;
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = ($originalWidth * $maxDimension) / $originalHeight;
                }
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Crear nueva imagen redimensionada
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Mantener transparencia para PNG
            if ($imageInfo['mime'] === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }

            // Redimensionar imagen
            imagecopyresampled(
                $resizedImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $originalWidth,
                $originalHeight
            );

            // 🗜️ COMPRIMIR ITERATIVAMENTE
            $quality = 85;
            $targetSizeBytes = 5 * 1024 * 1024; // 5MB target
            $attempts = 0;
            $maxAttempts = 8;

            do {
                ob_start();
                \imagejpeg($resizedImage, null, $quality);
                $imageData = ob_get_contents();
                ob_end_clean();

                $currentSize = strlen($imageData);

                if ($currentSize <= $targetSizeBytes) {
                    break;
                }

                $quality = max(10, $quality - 15);
                $attempts++;
            } while ($attempts < $maxAttempts);

            // Limpiar memoria
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            return $imageData;
        } catch (\Exception $e) {
            Log::error('❌ Error en compresión nativa', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ☁️ Subir imagen a Cloud Storage con compresión
     */
    public function subirImagenOptimizada($file, $nombreCampo, $prefix = 'observaciones/')
    {
        try {
            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }
            $tokenUnico = session('token_unico');

            $tempPath = $file->getRealPath();
            $imageInfo = getimagesize($tempPath);

            if (!$imageInfo) {
                throw new \Exception("No se pudo leer la información de la imagen");
            }

            // Comprimir imagen
            $imageData = $this->comprimirImagenNativa($tempPath, $imageInfo);
            if (!$imageData) {
                throw new \Exception("Error al comprimir imagen");
            }

            $finalSizeMB = strlen($imageData) / (1024 * 1024);

            // Validación final
            if ($finalSizeMB > 5.5) {
                throw new \Exception("Imagen aún muy grande: {$finalSizeMB}MB");
            }

            // Generar nombre único
            $filename = sprintf(
                '%s%s_%s_%s_%s.jpg',
                $prefix,
                $nombreCampo,
                $tokenUnico,
                time(),
                substr(md5($imageData), 0, 8)
            );

            // Subir a Cloud Storage
            $this->bucket->upload($imageData, [
                'name' => $filename,
                'metadata' => [
                    'contentType' => 'image/jpeg',
                    'cacheControl' => 'public, max-age=3600',
                    'customMetadata' => [
                        'campo_formulario' => $nombreCampo,
                        'session_id' => $tokenUnico,
                        'tamaño_comprimido' => round($finalSizeMB, 2) . 'MB',
                        'fecha_subida' => now()->toISOString()
                    ]
                ]
            ]);

            $publicUrl = sprintf(
                'https://storage.cloud.google.com/%s/%s',
                config('services.google.storage_bucket'),
                $filename
            );

            return $publicUrl;
        } catch (\Exception $e) {
            Log::error('❌ Error en subida nativa', [
                'error' => $e->getMessage(),
                'campo' => $nombreCampo
            ]);
            return null;
        }
    }
}
