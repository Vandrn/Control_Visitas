<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Helpers\EvaluacionHelper;
use Google\Cloud\BigQuery\BigQueryClient;

/**
 * 📋 Servicio de procesamiento de formularios
 * Maneja generación de resúmenes, cálculo de puntuaciones
 * y generación de archivos HTML para correos
 */
class FormProcessingService
{
    protected $bigQuery;

    public function __construct()
    {
        $this->bigQuery = new BigQueryClient([
            'projectId' => config('services.google.project_id'),
            'keyFilePath' => storage_path('app/' . config('services.google.keyfile')),
        ]);
    }

    /**
     * 📊 Calcular resumen de áreas con puntuaciones y estrellas
     */
    public function calcularResumen($secciones, $kpis)
    {
        $promediosPorArea = EvaluacionHelper::calcularPromediosPorArea($secciones, $kpis);
        $totales = EvaluacionHelper::calcularTotalPonderado($promediosPorArea);

        // helper para estrellas 0..5
        $toStars = function ($p01) {
            if (!is_numeric($p01)) return 0;
            $p01 = max(0, min(1, (float)$p01));          // clamp 0..1
            return (int) max(0, min(5, round($p01 * 5))); // 0..5
        };

        $resumenAreas = [];

        foreach ($promediosPorArea as $area => $info) {
            $prom = $info['promedio'] ?? null;

            // Normalizar a 0..1
            // Si viene 0..5 -> dividir entre 5
            // Si viene 0..1 -> dejarlo
            $p01 = null;
            if (is_numeric($prom)) {
                $prom = (float) $prom;
                $p01 = ($prom > 1.0001) ? ($prom / 5) : $prom;
                $p01 = max(0, min(1, $p01));
            }

            $resumenAreas[] = [
                'nombre' => ucfirst($area),
                // este es el valor que vos mostrabas como "0.94 puntos"
                'puntos' => is_null($p01) ? 'N/A' : round($p01, 2),
                'estrellas' => is_null($p01) ? 'N/A' : $toStars($p01),
            ];
        }

        // Totales también normalizados
        $puntajeTotal = $totales['puntaje'] ?? 0;
        if (is_numeric($puntajeTotal)) {
            $puntajeTotal = (float) $puntajeTotal;
            $puntajeTotal = ($puntajeTotal > 1.0001) ? ($puntajeTotal / 5) : $puntajeTotal;
            $puntajeTotal = max(0, min(1, $puntajeTotal));
        } else {
            $puntajeTotal = 0;
        }

        return [
            'resumen_areas' => $resumenAreas,
            'puntos_totales' => round($puntajeTotal, 2),
            'estrellas' => $toStars($puntajeTotal),
        ];
    }

    /**
     * 🔄 Procesar secciones desde BigQuery (convertir string a array)
     */
    public function procesarSecciones($seccionesData)
    {
        if (empty($seccionesData)) {
            return [];
        }

        $secciones = is_string($seccionesData) 
            ? json_decode($seccionesData, true) 
            : (array)$seccionesData;

        return is_array($secciones) ? $secciones : [];
    }

    /**
     * 🔄 Procesar KPIs desde BigQuery (convertir string a array)
     */
    public function procesarKPIs($kpisData)
    {
        if (empty($kpisData)) {
            return [];
        }

        $kpisArray = is_string($kpisData) 
            ? json_decode($kpisData, true) 
            : (array)$kpisData;
        
        $kpis = [];
        if (is_array($kpisArray)) {
            foreach ($kpisArray as $kpi) {
                if (is_object($kpi)) {
                    $kpis[] = [
                        'codigo_pregunta' => $kpi->codigo_pregunta ?? $kpi['codigo_pregunta'] ?? '',
                        'valor' => $kpi->valor ?? $kpi['valor'] ?? '',
                        'variacion' => $kpi->variacion ?? $kpi['variacion'] ?? null
                    ];
                } elseif (is_array($kpi)) {
                    $kpis[] = $kpi;
                }
            }
        }

        return $kpis;
    }

    /**
     * 📧 Generar archivo HTML para correo de confirmación
     */
    public function generarHTMLCorreo($datos, $correoOriginal)
    {
        try {
            Log::info('➡️ Generando HTML...');
            
            $html = view('emails.visita_confirmacion', ['datos' => $datos])->render();

            // Insertar comentario con los correos justo después del <body>
            $comentarioCorreos = "<!--\n";
            $comentarioCorreos .= "correo_realizo: {$correoOriginal}\n";
            $comentarioCorreos .= "correo_tienda: ".($datos['correo_tienda'] ?? 'NO ENCONTRADO')."\n";
            $comentarioCorreos .= "correo_jefe_zona: ".($datos['correo_jefe_zona'] ?? 'N/A')."\n";
            $comentarioCorreos .= "-->\n";

            $html = preg_replace_callback('/<body[^>]*>/', function ($matches) use ($comentarioCorreos) {
                return $matches[0] . "\n" . $comentarioCorreos;
            }, $html);

            Log::info('✅ HTML generado con comentario');

            // Crear nombre del archivo único
            $nombreArchivo = 'visita_' . Str::random(8) . '.html';

            // Carpeta completa en CPanel (public_html/retail/correos)
            $rutaCarpeta = $_SERVER['DOCUMENT_ROOT'] . '/retail/correos';

            if (!File::exists($rutaCarpeta)) {
                File::makeDirectory($rutaCarpeta, 0755, true);
            }

            // Ruta completa del archivo
            $rutaArchivo = $rutaCarpeta . '/' . $nombreArchivo;

            // Guardar el archivo HTML
            File::put($rutaArchivo, $html);

            // Generar URL pública para Power Automate
            $urlHtml = url('retail/correos/' . $nombreArchivo);

            Log::info('✅ HTML de visita generado y guardado para Power Automate', [
                'url' => $urlHtml
            ]);

            return $urlHtml;
        } catch (\Exception $e) {
            Log::error('❌ Error al generar archivo HTML', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ Validar preguntas con imagen obligatoria
     */
    public function validarImagenesObligatorias($secciones)
    {
        $preguntasConImagen = [
            2 => [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 21, 22], // Operaciones
            4 => [1, 2, 5, 6, 7, 8, 9], // Producto
            5 => [1, 9] // Personal
        ];

        $mapaSeccionNombreNumero = [
            'operaciones' => 2,
            'producto' => 4,
            'personal' => 5
        ];

        $mapaCodigos = [
            2 => array_map(function($n) { return sprintf('PREG_01_%02d', $n); }, $preguntasConImagen[2]),
            4 => array_map(function($n) { return sprintf('PREG_03_%02d', $n); }, $preguntasConImagen[4]),
            5 => array_map(function($n) { return sprintf('PREG_04_%02d', $n); }, $preguntasConImagen[5]),
        ];

        foreach ($secciones as $seccion) {
            $nombre = $seccion['nombre_seccion'] ?? '';
            $numSeccion = $mapaSeccionNombreNumero[$nombre] ?? null;

            if (!$numSeccion || !isset($preguntasConImagen[$numSeccion])) {
                continue;
            }

            $codigosValidar = $mapaCodigos[$numSeccion] ?? [];

            foreach ($seccion['preguntas'] as $pregunta) {
                $codigo = $pregunta['codigo_pregunta'] ?? '';

                if (in_array($codigo, $codigosValidar)) {
                    if (empty($pregunta['imagenes']) || count($pregunta['imagenes']) < 1) {
                        return [
                            'valido' => false,
                            'error' => 'Debes subir al menos una imagen en la pregunta ' . $codigo . ' de la sección ' . $numSeccion
                        ];
                    }
                }
            }
        }

        return ['valido' => true];
    }

    /**
     * 🔗 Normalizar URLs de imágenes
     */
    public function normalizarURLsImagenes(&$secciones)
    {
        foreach ($secciones as &$seccion) {
            foreach ($seccion['preguntas'] as &$pregunta) {
                if (!isset($pregunta['imagenes']) || !is_array($pregunta['imagenes'])) {
                    $pregunta['imagenes'] = [];
                }

                $pregunta['imagenes'] = collect($pregunta['imagenes'])
                    ->filter(fn($url) => is_string($url) && str_starts_with($url, 'http'))
                    ->take(5)
                    ->values()
                    ->all();
            }
        }
    }
}
