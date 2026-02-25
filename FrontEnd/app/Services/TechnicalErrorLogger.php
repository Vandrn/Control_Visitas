<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ⚠️ Servicio de registro de errores técnicos
 * Detecta y registra errores técnicos automáticamente
 * en storage/logs/errores-tecnicos.log
 * 
 * 📝 IMPORTANTE: Registra IP, tienda y correo del usuario
 */
class TechnicalErrorLogger
{
    /**
     * 🆕 Detectar si un mensaje es error técnico
     */
    public function esErrorTecnico($mensaje)
    {
        $palabrasClave = [
            'STRUCT', 'JSON', 'type', 'undefined', 'null', 'exception',
            'error', 'failed', 'invalid', 'INVALID_ARGUMENT', 'SYNTAX_ERROR',
            'PARSE', 'parsing', 'unexpected', 'Cannot', 'cannot', 'must',
            'required', 'constraint', 'foreign key', 'database',
            'BigQuery', 'SQL', 'query', 'parameter', 'token', 'Type'
        ];
        
        foreach ($palabrasClave as $palabra) {
            if (stripos($mensaje, $palabra) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 🆕 Obtener contexto del usuario (correo y tienda)
     */
    private function obtenerContextoUsuario($detalles = [])
    {
        $contexto = [
            'correo' => null,
            'tienda' => null
        ];

        // Intentar obtener del request directamente
        $correo = request()->input('correo_realizo') ?? request()->input('correo') ?? null;
        $tienda = request()->input('tienda') ?? null;

        // Si está en detalles, usar esos valores
        if (isset($detalles['correo'])) {
            $correo = $detalles['correo'];
        }
        if (isset($detalles['tienda'])) {
            $tienda = $detalles['tienda'];
        }

        $contexto['correo'] = $correo;
        $contexto['tienda'] = $tienda;

        return $contexto;
    }

    /**
     * 🆕 Parsear y extraer mensaje de error legible
     * Extrae el mensaje principal del error (sin todo el JSON escapado)
     */
    private function extraerMensajeError($mensaje)
    {
        // Si es un error de BigQuery con JSON escapado, extraer el mensaje
        if (strpos($mensaje, '\"message\"') !== false) {
            // Intenta parsear como JSON
            $json = json_decode($mensaje, true);
            if (is_array($json)) {
                if (isset($json['error']['message'])) {
                    return $json['error']['message'];
                } elseif (isset($json['message'])) {
                    return $json['message'];
                }
            }
        }

        // Si contiene "Error: {", intenta extraer de esa estructura
        if (preg_match('/Error:\s*\{(.*?)\}/s', $mensaje, $matches)) {
            $jsonPart = '{' . $matches[1] . '}';
            $json = json_decode($jsonPart, true);
            if (is_array($json) && isset($json['error']['message'])) {
                return $json['error']['message'];
            }
        }

        // Si nada funciona, retorna el mensaje original pero limpio
        return trim(strip_tags($mensaje));
    }

    /**
     * 🆕 Extraer detalles structured del error
     */
    private function extraerDetallesError($mensaje)
    {
        $detalles = [
            'tipo_error' => 'Desconocido',
            'codigo' => null,
            'razon' => null
        ];

        // Intenta parsear como JSON
        $json = json_decode($mensaje, true);
        if (is_array($json)) {
            if (isset($json['error']['code'])) {
                $detalles['codigo'] = $json['error']['code'];
            }
            if (isset($json['error']['status'])) {
                $detalles['tipo_error'] = $json['error']['status'];
            }
            if (isset($json['error']['errors'][0]['reason'])) {
                $detalles['razon'] = $json['error']['errors'][0]['reason'];
            }
        }

        // Detectar tipo de error por palabras clave
        if (stripos($mensaje, 'concurrent') !== false) {
            $detalles['tipo_error'] = 'CONCURRENT_UPDATE';
        } elseif (stripos($mensaje, 'INVALID_ARGUMENT') !== false) {
            $detalles['tipo_error'] = 'INVALID_ARGUMENT';
        } elseif (stripos($mensaje, 'PERMISSION_DENIED') !== false) {
            $detalles['tipo_error'] = 'PERMISSION_DENIED';
        } elseif (stripos($mensaje, 'NOT_FOUND') !== false) {
            $detalles['tipo_error'] = 'NOT_FOUND';
        }

        return $detalles;
    }

    /**
     * 🆕 Registrar error técnico con contexto completo y estructura mejorada
     */
    public function registrar($metodo, $sessionId, $mensaje, $detalles = [])
    {
        $contextoUsuario = $this->obtenerContextoUsuario($detalles);
        $mensajeLimpio = $this->extraerMensajeError($mensaje);
        $detallesError = $this->extraerDetallesError($mensaje);

        Log::channel('technical_errors')->error('⚠️ ERROR TÉCNICO DETECTADO', [
            'metodo' => $metodo,
            'session_id' => $sessionId,
            'correo_usuario' => $contextoUsuario['correo'] ?? 'No disponible',
            'tienda' => $contextoUsuario['tienda'] ?? 'No seleccionada',
            'ip_usuario' => request()->ip(),
            'url' => request()->url(),
            'error_tecnico' => $mensajeLimpio,
            'tipo_error' => $detallesError['tipo_error'],
            'codigo_error' => $detallesError['codigo'],
            'razon' => $detallesError['razon'],
            'detalles_adicionales' => $detalles,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * 🆕 Registrar error técnico si corresponde
     */
    public function registrarSiEsErrorTecnico($metodo, $sessionId, $mensaje, $detalles = [])
    {
        if ($this->esErrorTecnico($mensaje)) {
            $this->registrar($metodo, $sessionId, $mensaje, $detalles);
        }
    }
}
