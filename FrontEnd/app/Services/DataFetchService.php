<?php

namespace App\Services;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Facades\Log;

/**
 * 🌍 Servicio de obtención de datos desde BigQuery
 * Maneja consultas para países, zonas y tiendas
 */
class DataFetchService
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
     * 🌎 Obtener lista de países
     */
    public function obtenerPaises()
    {
        $query = "
            SELECT DISTINCT T001W.LAND1 AS BV_PAIS
            FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
            WHERE ADRC.COUNTRY IN ('SV', 'GT', 'HN', 'CR', 'NI', 'PA')
            AND T001W.VLFKZ = 'A'
            AND ADRC.PO_BOX <> 'CL'
            AND ADRC.SORT1 NOT IN ('WHS','BT1')
            AND TVKTT.SPRAS = 'S'
            AND ADR6.SMTP_ADDR IS NOT NULL
        ";

        $queryJobConfig = $this->bigQuery->query($query)
            ->useLegacySql(false)
            ->location('US');

        $results = $this->bigQuery->runQuery($queryJobConfig);

        $paises = [];
        foreach ($results->rows() as $row) {
            $paises[] = [
                'label' => $row['BV_PAIS'],
                'value' => $row['BV_PAIS']
            ];
        }

        return $paises;
    }

    /**
     * 🏘️ Obtener zonas por país
     */
    public function obtenerZonas($bv_pais)
    {
        $query = "
            SELECT DISTINCT ADRC.NAME3 AS ZONA
            FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
            WHERE T001W.LAND1 = @bv_pais
            AND T001W.VLFKZ = 'A'
            AND ADRC.PO_BOX <> 'CL'
            AND ADRC.SORT1 NOT IN ('WHS','BT1')
            AND TVKTT.SPRAS = 'S'
            AND ADR6.SMTP_ADDR IS NOT NULL
        ";

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters(['bv_pais' => $bv_pais])
            ->useLegacySql(false)
            ->location('US');

        $results = $this->bigQuery->runQuery($queryJobConfig);

        $zonas = [];
        foreach ($results->rows() as $row) {
            $zona = $row['ZONA'];
            // Si es Panamá, normaliza cualquier variante de 'Zona I' a 'Zona I'
            if ($bv_pais === 'PA' && preg_match('/^zona i$/i', $zona)) {
                $zona = 'Zona I';
            }
            $zonas[] = $zona;
        }

        // Si es Panamá, mostrar solo 'Zona I' si existe alguna variante
        if ($bv_pais === 'PA') {
            $tieneZonaI = false;
            foreach ($zonas as $z) {
                if (preg_match('/^zona i$/i', $z)) {
                    $tieneZonaI = true;
                    break;
                }
            }
            if ($tieneZonaI) {
                $zonas = ['Zona I'];
            }
        }

        return $zonas;
    }

    /**
     * 🏪 Obtener tiendas por país y zona
     */
    public function obtenerTiendas($bv_pais, $zona)
    {
        // Si es Panamá y la zona es 'Zona I', buscar ambas variantes
        if ($bv_pais === 'PA' && preg_match('/^zona i$/i', $zona)) {
            $query = "
                SELECT 
                    ADRC.NAME1 AS TIENDA,
                    ADRC.NAME2 AS UBICACION,
                    CONCAT(GEO.LATITUD, ',', GEO.LONGITUD) AS GEO
                FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                    ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
                LEFT JOIN `adoc-bi-prd`.`BI_Repo_Qlik.DIM_GEOLOCALIZACION` AS GEO ON GEO.WERKS = CAST(T001W.WERKS AS INT64)
                WHERE T001W.LAND1 = @bv_pais
                AND (ADRC.NAME3 = 'Zona I' OR ADRC.NAME3 = 'ZONA I')
                AND T001W.VLFKZ = 'A'
                AND ADRC.PO_BOX <> 'CL'
                AND ADRC.SORT1 NOT IN ('WHS','BT1')
                AND TVKTT.SPRAS = 'S'
                AND ADR6.SMTP_ADDR IS NOT NULL
            ";
            $queryJobConfig = $this->bigQuery->query($query)
                ->parameters(['bv_pais' => $bv_pais])
                ->useLegacySql(false)
                ->location('US');
        } else {
            $query = "
                SELECT 
                    ADRC.NAME1 AS TIENDA,
                    ADRC.NAME2 AS UBICACION,
                    CONCAT(GEO.LATITUD, ',', GEO.LONGITUD) AS GEO
                FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                    ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
                LEFT JOIN `adoc-bi-prd`.`BI_Repo_Qlik.DIM_GEOLOCALIZACION` AS GEO ON GEO.WERKS = CAST(T001W.WERKS AS INT64)
                WHERE T001W.LAND1 = @bv_pais
                AND ADRC.NAME3 = @zona
                AND T001W.VLFKZ = 'A'
                AND ADRC.PO_BOX <> 'CL'
                AND ADRC.SORT1 NOT IN ('WHS','BT1')
                AND TVKTT.SPRAS = 'S'
                AND ADR6.SMTP_ADDR IS NOT NULL
            ";
            $queryJobConfig = $this->bigQuery->query($query)
                ->parameters([
                    'bv_pais' => $bv_pais,
                    'zona' => $zona
                ])
                ->useLegacySql(false)
                ->location('US');
        }

        $results = $this->bigQuery->runQuery($queryJobConfig);

        $tiendas = [];
        foreach ($results->rows() as $row) {
            $tiendas[] = [
                'TIENDA' => $row['TIENDA'],
                'UBICACION' => $row['UBICACION'],
                'GEO' => $row['GEO'] ?? null
            ];
        }

        return $tiendas;
    }

    /**
     * 📧 Obtener correo de tienda
     */
    public function obtenerCorreoTienda($crmIdTiendaCompleto, $pais)
    {
        $query = $this->bigQuery->query(<<<'SQL'
WITH emails AS (
  SELECT ADDRNUMBER, ANY_VALUE(SMTP_ADDR) AS SMTP_ADDR
  FROM `adoc-bi-prd`.`SAP_ECC`.`ADR6`
  WHERE SMTP_ADDR IS NOT NULL AND SMTP_ADDR != ''
  GROUP BY ADDRNUMBER
)
SELECT
  w.LAND1 AS Pais,
  CONCAT(w.LAND1, COALESCE(a.SORT1,'')) AS Pais_Tienda,
  e.SMTP_ADDR AS Email
FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS w
JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS c ON c.KUNNR = w.KUNNR
LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS a ON a.ADDRNUMBER = c.ADRNR
LEFT JOIN emails AS e ON e.ADDRNUMBER = a.ADDRNUMBER
WHERE
  w.LAND1 IN ('SV','GT','HN','CR','NI','PA')
  AND w.VLFKZ = 'A'
  AND UPPER(a.SORT1) NOT IN ('WHS','BT1')
  AND CONCAT(w.LAND1, COALESCE(a.SORT1,'')) = @tienda
  AND w.LAND1 = @pais
LIMIT 1
SQL
        )->parameters([
            'tienda' => $crmIdTiendaCompleto,
            'pais'   => $pais
        ])
        ->useLegacySql(false)
        ->location('US');

        Log::info('➡️ Consultando correo tienda...');
        Log::info("🔎 Buscando correo de tienda con:", [
            'pais_tienda' => $crmIdTiendaCompleto,
            'pais' => $pais
        ]);

        $result = $this->bigQuery->runQuery($query);
        Log::info('✅ Consulta correo tienda completada');

        $correoTienda = null;
        foreach ($result->rows() as $row) {
            Log::info("📥 Resultado de correo tienda:", (array) $row);
            if (!empty($row['Email'])) {
                $correoTienda = $row['Email'];
                break;
            }
            if (!empty($row['email'])) {
                $correoTienda = $row['email'];
                break;
            }
        }

        return $correoTienda;
    }

    /**
     * 👔 Obtener correo de jefe de zona
     */
    public function obtenerCorreoJefe($pais)
    {
        $query = $this->bigQuery->query(
            'SELECT CORREO_GERENTE FROM `adoc-bi-dev.DEV_OPB.dim_gerentes`
            WHERE BV_PAIS = @bv_pais'
        )->parameters([
            'bv_pais' => $pais
        ])
        ->useLegacySql(false)
        ->location('US');

        Log::info('➡️ Consultando correo jefe...');
        Log::info("🔎 Buscando correo de jefe con:", [
            'BV_PAIS' => $pais
        ]);

        $result = $this->bigQuery->runQuery($query);
        Log::info('✅ Consulta correo jefe completada');

        $correoJefe = null;
        foreach ($result->rows() as $row) {
            Log::info("📥 Resultado de correo jefe:", (array) $row);
            $correoJefe = $row['CORREO_GERENTE'] ?? null;
        }

        return $correoJefe;
    }
}
