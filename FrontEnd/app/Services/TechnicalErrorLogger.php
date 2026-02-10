<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ⚠️ Servicio de registro de errores técnicos
 * Detecta y registra errores técnicos automáticamente
 * en storage/logs/errores-tecnicos.log
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
     * 🆕 Registrar error técnico con contexto
     */
    public function registrar($metodo, $sessionId, $mensaje, $detalles = [])
    {
        Log::channel('technical_errors')->error('⚠️ ERROR TÉCNICO DETECTADO', [
            'metodo' => $metodo,
            'session_id' => $sessionId,
            'error_tecnico' => $mensaje,
            'detalles' => $detalles,
            'timestamp' => now()->toIso8601String(),
            'url' => request()->url(),
            'ip_usuario' => request()->ip()
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
