<?php

namespace App\Helpers;

class EvaluacionHelper
{
    // 1️⃣ Calcula promedio por área (sin observaciones)
    public static function calcularPromediosPorArea(array $secciones, array $kpis = []): array
    {
        $resultados = [];

        foreach ($secciones as $seccion) {
            $nombre = $seccion['nombre_seccion'];
            $preguntas = $seccion['preguntas'] ?? [];

            $valores = [];
            foreach ($preguntas as $preg) {
                if (!str_starts_with($preg['codigo_pregunta'], 'OBS_')) {
                    $valor = floatval($preg['respuesta'] ?? 0);
                    if ($valor > 0) {
                        $valores[] = $valor;
                    }
                }
            }

            $promedio = count($valores) > 0 ? round(array_sum($valores) / count($valores), 2) : null;

            $resultados[$nombre] = [
                'promedio' => $promedio,
                'total_preguntas' => count(value: $valores),
            ];
        }

        // ✅ Agregar KPIs como sección especial
        if (!empty($kpis)) {
            $puntaje = 0;
            $total = 0;

            foreach ($kpis as $kpi) {
                if (!str_starts_with($kpi['codigo_pregunta'], 'OBS_')) {
                    $respuesta = strtolower(trim($kpi['valor'] ?? ''));

                    if ($respuesta === 'cumple') {
                        $puntaje += 1;
                        $total++;
                    } elseif ($respuesta === 'no cumple') {
                        $total++;
                    }
                }
            }

            $promedio = $total > 0 ? round($puntaje / $total, 2) : null;

            $resultados['kpi'] = [
                'promedio' => $promedio,
                'total_preguntas' => $total,
            ];
        }
        return $resultados;
    }


    // 2️⃣ Calcula el puntaje total ponderado y estrellas
    public static function calcularTotalPonderado(array $promedios): array
    {
        // Pesos establecidos manualmente según número de preguntas
        $pesos = [
            'operaciones' => 0.40,
            'administracion' => 0.127,
            'producto' => 0.164,
            'personal' => 0.20,
            'kpi' => 0.109,
        ];

        $puntajeTotal = 0;
        foreach ($pesos as $area => $peso) {
            if (isset($promedios[$area]['promedio'])) {
                $puntajeTotal += $promedios[$area]['promedio'] * $peso;
            }
        }

        // Redondeamos a 2 decimales
        $puntajeTotal = round($puntajeTotal, 2);

        // Convertimos a estrellas (1 estrella = 0.2 puntos)
        $estrellas = intval(round($puntajeTotal / 0.2));

        return [
            'puntaje' => $puntajeTotal,
            'estrellas' => $estrellas,
        ];
    }
}
