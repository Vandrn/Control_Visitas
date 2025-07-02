<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient; // AGREGAR ESTA LÍNEA AL INICIO
use Illuminate\Support\Facades\Log;

class VisitaController extends Controller
{
    protected $usuario;

    public function __construct()
    {
        $this->usuario = new Usuario();
    }

    /**
     * Mostrar detalle completo de una visita
     */
    public function show($id)
    {
        try {
            $user = session('admin_user');
            
            // Obtener visita completa
            $visitaRaw = $this->usuario->getVisitaCompleta(
                $id, 
                $user['rol'], 
                $user['email']
            );
            
            if (!$visitaRaw) {
                abort(404, 'Visita no encontrada o sin permisos para verla.');
            }
            
            // AGREGAR VALIDACI�0�7N DE ACCESO POR PA�0�1S
            if (!$this->validarAccesoPais($visitaRaw, $user)) {
                abort(403, 'No tiene permisos para ver visitas de este pa��s.');
            }

            // Procesar datos para display
            $visita = $this->usuario->procesarDatosVisita($visitaRaw);
            
            // Calcular puntuaciones
            $puntuaciones = $this->usuario->calcularPuntuaciones($visita);

// Obtener textos de preguntas
$textosPreguntas = $this->getTextosPreguntas();

// 📍 AGREGAR VALIDACIÓN DE DISTANCIA
$validacionDistancia = $this->usuario->getValidacionDistancia(
    $id, 
    $user['rol'], 
    $user['email']
);

return view('admin.visitas.show', compact(
    'visita',
    'puntuaciones', 
    'textosPreguntas',
    'validacionDistancia'
));

        } catch (\Exception $e) {
            Log::error('Error al mostrar visita: ' . $e->getMessage());
            
            if ($e->getCode() === 404) {
                abort(404);
            }
            
            return redirect()->route('admin.dashboard')
                ->with('error', 'Error al cargar el detalle de la visita.');
        }
    }

/**
 * Mostrar galería de imágenes de una visita - VERSIÓN CORREGIDA CON TUS VARIABLES .env
 */
public function imagenes($id)
{
    try {
        $user = session('admin_user');
        
        // Obtener la visita completa
        $visitaRaw = $this->usuario->getVisitaCompleta(
            $id, 
            $user['rol'], 
            $user['email']
        );

        if (!$visitaRaw) {
            abort(404, 'Visita no encontrada o sin permisos para verla.');
        }
        
        // AGREGAR ESTA VALIDACI�0�7N:
        // Validar acceso por pa��s
        if (!$this->validarAccesoPais($visitaRaw, $user)) {
            abort(403, 'No tiene permisos para ver im��genes de visitas de este pa��s.');
        }

        // Inicializar Google Cloud Storage CON TUS VARIABLES .env
        $storage = new StorageClient([
            'projectId' => env('BIGQUERY_PROJECT_ID', 'adoc-bi-dev'),
            'keyFilePath' => storage_path('app' . env('BIGQUERY_KEY_FILE', '/claves/adoc-bi-dev-debcb06854ae.json'))
        ]);
        
        $bucketName = env('GOOGLE_CLOUD_STORAGE_BUCKET', 'adoc-bi-dev-control-visitas-lz');
        $bucket = $storage->bucket($bucketName);

        // Extraer todas las URLs de imágenes con URLs firmadas
        $imagenes = [];
        $areasMapping = [
            'IMG_OBS_OPE' => [
                'titulo' => 'Operaciones', 
                'observaciones' => $visitaRaw['OBS_01_01'] ?? null
            ],
            'IMG_OBS_ADM' => [
                'titulo' => 'Administración', 
                'observaciones' => $visitaRaw['OBS_02_01'] ?? null
            ],
            'IMG_OBS_PRO' => [
                'titulo' => 'Producto', 
                'observaciones' => $visitaRaw['OBS_03_01'] ?? null
            ],
            'IMG_OBS_PER' => [
                'titulo' => 'Personal', 
                'observaciones' => $visitaRaw['OBS_04_01'] ?? null
            ],
            'IMG_OBS_KPI' => [
                'titulo' => 'KPIs', 
                'observaciones' => $visitaRaw['OBS_05_01'] ?? null
            ],
        ];

        foreach ($areasMapping as $columna => $info) {
            if (!empty($visitaRaw[$columna])) {
                try {
                    // Extraer el nombre del archivo de la URL completa
                    $urlOriginal = $visitaRaw[$columna];
                    $fileName = basename(parse_url($urlOriginal, PHP_URL_PATH));
                    
                    // Log para debugging
                    Log::info("Procesando imagen: $columna");
                    Log::info("URL Original: $urlOriginal");
                    Log::info("Nombre archivo: $fileName");
                    
                    // Generar URL firmada válida por 2 horas
                    $object = $bucket->object('observaciones/' . $fileName);
                    
                    // Verificar si el objeto existe
                    if ($object->exists()) {
                        $signedUrl = $object->signedUrl(new \DateTime('+2 hours'));
                        Log::info("URL Firmada generada: $signedUrl");
                        
                        $imagenes[] = [
                            'area' => $columna,
                            'titulo' => $info['titulo'],
                            'url' => $signedUrl,
                            'url_original' => $urlOriginal,
                            'observaciones' => $info['observaciones'],
                            'existe' => true
                        ];
                    } else {
                        Log::warning("Archivo no encontrado en bucket: observaciones/$fileName");
                        
                        // Intentar sin la carpeta observaciones
                        $objectDirect = $bucket->object($fileName);
                        if ($objectDirect->exists()) {
                            $signedUrl = $objectDirect->signedUrl(new \DateTime('+2 hours'));
                            Log::info("URL Firmada (directa) generada: $signedUrl");
                            
                            $imagenes[] = [
                                'area' => $columna,
                                'titulo' => $info['titulo'],
                                'url' => $signedUrl,
                                'url_original' => $urlOriginal,
                                'observaciones' => $info['observaciones'],
                                'existe' => true
                            ];
                        } else {
                            // Si no existe, usar URL original como fallback
                            $imagenes[] = [
                                'area' => $columna,
                                'titulo' => $info['titulo'],
                                'url' => $urlOriginal,
                                'url_original' => $urlOriginal,
                                'observaciones' => $info['observaciones'],
                                'existe' => false
                            ];
                        }
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Error generando URL firmada para $columna: " . $e->getMessage());
                    
                    // Si falla todo, usar la URL original
                    $imagenes[] = [
                        'area' => $columna,
                        'titulo' => $info['titulo'],
                        'url' => $visitaRaw[$columna],
                        'url_original' => $visitaRaw[$columna],
                        'observaciones' => $info['observaciones'],
                        'existe' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        // Información básica de la visita
        $infoVisita = [
            'id' => $visitaRaw['id'],
            'tienda' => $visitaRaw['TIENDA'] ?? 'N/A',
            'pais' => $visitaRaw['PAIS'] ?? 'N/A',
            'zona' => $visitaRaw['ZONA'] ?? 'N/A',
            'fecha' => $visitaRaw['FECHA_HORA_INICIO'],
            'evaluador' => $visitaRaw['LIDER_ZONA'] ?? $visitaRaw['CORREO_REALIZO']
        ];

        Log::info("Total de imágenes procesadas: " . count($imagenes));

        return view('admin.visitas.imagenes', compact('imagenes', 'infoVisita'));

    } catch (\Exception $e) {
        Log::error('Error al mostrar imágenes: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return redirect()->route('admin.dashboard')
            ->with('error', 'Error al cargar las imágenes de la visita: ' . $e->getMessage());
    }
}


    /**
     * API para obtener datos de visita (AJAX)
     */
    public function getVisitaData($id)
    {
        try {
            $user = session('admin_user');
            
            $visitaRaw = $this->usuario->getVisitaCompleta(
                $id, 
                $user['rol'], 
                $user['email']
            );

            if (!$visitaRaw) {
                return response()->json([
                    'success' => false,
                    'error' => 'Visita no encontrada'
                ], 404);
            }
            
            // AGREGAR ESTA VALIDACI�0�7N:
            // Validar acceso por pa��s
            if (!$this->validarAccesoPais($visitaRaw, $user)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Sin acceso a visitas de este pa��s'
                ], 403);
            }

            $visita = $this->usuario->procesarDatosVisita($visitaRaw);
            $puntuaciones = $this->usuario->calcularPuntuaciones($visita);

            // �9�9 AGREGAR VALIDACI�0�7N DE DISTANCIA PARA API
            $validacionDistancia = $this->usuario->getValidacionDistancia(
                $id, 
                $user['rol'], 
                $user['email']
            );

return response()->json([
    'success' => true,
    'data' => $visita,
    'puntuaciones' => $puntuaciones,
    'validacion_distancia' => $validacionDistancia
]);

        } catch (\Exception $e) {
            Log::error('Error en getVisitaData API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener textos de las preguntas para display
     */
    private function getTextosPreguntas()
    {
        return [
            'operaciones' => [
                'PREG_01_01' => 'Pintura de tienda en buen estado. Interior/Exterior.',
                'PREG_01_02' => 'Vitrinas de tiendas limpias, con iluminación y acrílicos en buen estado.',
                'PREG_01_03' => 'Exhibición de producto en vitrina según estándares.',
                'PREG_01_04' => 'Sala de ventas limpia, ordenada y con iluminación en buen estado.',
                'PREG_01_05' => 'Aires acondicionados/ventiladores y escaleras en buen estado.',
                'PREG_01_06' => 'Repisas, mesas y muebles de exhibición limpios y en buen estado.',
                'PREG_01_07' => 'Mueble de caja limpio, ordenado y en buen estado',
                'PREG_01_08' => 'Equipo funcionando (radio, tel., cel., conteo de clientes, eq. de computo).',
                'PREG_01_09' => 'Utilización de la radio ADOC para ambientar la tienda.',
                'PREG_01_10' => 'Bodega limpia, con iluminación en buen estado y ordenada según manual.',
                'PREG_01_11' => 'Accesorios de limpieza ordenados y ubicados en el lugar adecuado.',
                'PREG_01_12' => 'Área de comida limpia y articulos personales ordenados en su área.',
                'PREG_01_13' => 'Baño limpio y ordenado',
                'PREG_01_14' => 'La tienda cuenta con suficientes sillas o bancos en buen estado (limpios y lavados) para que los clientes se prueben los zapatos (según planograma o layout). NOTA: Si los sillones están sucios deben mandarse a lavar.',
            ],
            'administracion' => [
                'PREG_02_01' => 'Cuenta de orden al día.',
                'PREG_02_02' => 'Documentos de transferencias y envíos ingresados al sistema al día',
                'PREG_02_03' => 'Remesas de efectivo al día e ingresados al sistema',
                'PREG_02_04' => 'Libro de cuadre de efectivo y caja chica al día',
                'PREG_02_05' => 'Libro de horarios al día y firmados por los empleados',
                'PREG_02_06' => 'Conteo efectuados según lineamientos establecidos.',
                'PREG_02_07' => 'Pizarras y folders Friedman actualizados.',
                'PREG_02_08' => 'Files actualizados.',
            ],
            'producto' => [
                'PREG_03_01' => 'Nuevos estilos exhibidos en sala de venta.',
                'PREG_03_02' => 'Artículos exhibidos con su etiqueta y precio correcto. Nota: Si un zapato llega dañado de fábrica reportarlo de inmediato y retírelo del piso de venta.',
                'PREG_03_03' => 'Cambios de precio realizado, firmado y archivado. Nota: Es prohibido colocar otro precio que no sea el oficial.',
                'PREG_03_04' => 'Promociones actualizadas y compartidas con todo el personal.',
                'PREG_03_05' => 'Reporte 80/20 revisado semanalmente.',
                'PREG_03_06' => 'Implementación de planogramas(Producto, POP, Manuales).',
                'PREG_03_07' => 'En las exhibiciones están todos los estilos disponibles en la tienda representados por talla (sin ningún zapato dañado o sucio).',
                'PREG_03_08' => 'Todas las sandalias en exhibidores y/o mesas usan modeladores acrílicos.',
            ],
            'personal' => [
                'PREG_04_01' => 'Cumplimiento de las marcaciones (4 por día).',
                'PREG_04_02' => 'Personal con imagen presentable, con su respectivo uniforme según política.',
                'PREG_04_03' => 'Personal cumple los 5 estándares NO negociables.',
                'PREG_04_04' => 'Amabilidad en el recibimiento de los clientes.',
                'PREG_04_05' => 'Cumplimiento de protocolos de bioseguridad.',
                'PREG_04_06' => 'Disponibilidad del personal para ayudar durante el recorrido, selección y prueba de calzado.',
                'PREG_04_07' => 'Nuestros ADOCKERS ofrecen ayuda a todos los clientes.',
                'PREG_04_08' => 'Nuestros ADOCKERS ofrecen encontrar la talla que el cliente pide y si no hay talla, ofrecen alternativas.',
                'PREG_04_09' => 'Nuestros ADOCKERS ofrecen medir el pie de los niños.',
                'PREG_04_10' => 'Se ofrecen diferentes zapatos para que ajuste el pie correctamente cuando hay niños.',
                'PREG_04_11' => 'Nuestros ADOCKERS elogian a los clientes por su elección de producto.',
                'PREG_04_12' => 'Nuestros clientes son atendidos rápidamente en caja.',
                'PREG_04_13' => '¿Han realizado los cursos de Academia ADOC?',
                'PREG_04_14' => '¿Adockers hacen uso de la APP ADOCKY cuando atienden a los clientes en el piso de venta?',
                'PREG_04_15' => 'Adockers hacen uso de la APP ADOCKY para realizar la representación de inventario.',
            ],
            'kpis' => [
                'PREG_05_01' => 'Venta',
                'PREG_05_02' => 'Margen',
                'PREG_05_03' => 'Conversión',
                'PREG_05_04' => 'UPT',
                'PREG_05_05' => 'DPT',
                'PREG_05_06' => 'NPS',
            ]
        ];
    }
    
    /**
 * Validar si el usuario tiene acceso a visitas del pa��s de la visita
 */
private function validarAccesoPais($visitaData, $userData)
{
    // Admin y evaluador normal tienen acceso completo
    if (in_array($userData['rol'], ['admin', 'evaluador'])) {
        return true;
    }
    
    // Para evaluador_pais, verificar pa��s espec��fico
    if ($userData['rol'] === 'evaluador_pais') {
        $paisVisita = $visitaData['PAIS'] ?? null;
        $paisPermitido = $userData['pais_acceso'] ?? null;
        
        // Log del intento de acceso para auditor��a
        Log::info('Validaci��n acceso por pa��s', [
            'usuario' => $userData['email'],
            'rol' => $userData['rol'],
            'pais_visita' => $paisVisita,
            'pais_permitido' => $paisPermitido,
            'visita_id' => $visitaData['id'] ?? 'N/A'
        ]);
        
        // Verificar acceso
        if ($paisPermitido === 'ALL') {
            return true;
        }
        
        if ($paisPermitido === $paisVisita) {
            return true;
        }
        
        // Log de acceso denegado
        Log::warning('Acceso denegado por pa��s', [
            'usuario' => $userData['email'],
            'pais_solicitado' => $paisVisita,
            'pais_permitido' => $paisPermitido,
            'visita_id' => $visitaData['id'] ?? 'N/A'
        ]);
        
        return false;
    }
    
    return false;
}
}