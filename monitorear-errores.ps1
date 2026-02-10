#!/usr/bin/env pwsh
<#
.SYNOPSIS
Script para monitorear errores técnicos del formulario en tiempo real

.DESCRIPTION
Muestra los errores técnicos del formulario a medida que se generan.
Los errores incluyen:
- Fecha/hora exacta
- Método donde ocurrió (saveDatos, saveKPIs, etc)
- session_id del formulario
- Mensaje de error técnico completo
- IP del usuario que generó el error

.EXAMPLE
.\monitorear-errores.ps1
#>

Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║         MONITOR DE ERRORES TÉCNICOS - FORMULARIO            ║" -ForegroundColor Cyan
Write-Host "║                   Control Visitas v1.0                       ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$logFile = "c:\copi\Control_Visitas\FrontEnd\storage\logs\errores-tecnicos.log"

if (-not (Test-Path $logFile)) {
    Write-Host "⏳ Esperando primer error..." -ForegroundColor Yellow
    Write-Host "📁 Archivo de logs: $logFile" -ForegroundColor Gray
    Write-Host ""
}

$lastPosition = 0

while ($true) {
    if (Test-Path $logFile) {
        $content = Get-Content $logFile -Raw
        $currentSize = (Get-Item $logFile).Length
        
        if ($currentSize -gt $lastPosition) {
            $newContent = $content.Substring($lastPosition)
            $lastPosition = $currentSize
            
            # Procesar nuevo contenido
            $lineas = $newContent -split "`n" | Where-Object { $_.Trim() -ne "" }
            
            foreach ($linea in $lineas) {
                if ($linea -match "ERROR TÉCNICO DETECTADO") {
                    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Red
                    Write-Host "⚠️  ERROR TÉCNICO DETECTADO" -ForegroundColor Red -BackgroundColor Black
                    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Red
                }
                
                # Resaltar información importante
                if ($linea -match '"metodo"') {
                    Write-Host "  $linea" -ForegroundColor Yellow
                } elseif ($linea -match '"session_id"') {
                    Write-Host "  $linea" -ForegroundColor Cyan
                } elseif ($linea -match '"ip_usuario"') {
                    Write-Host "  $linea" -ForegroundColor Magenta
                } elseif ($linea -match '"error_tecnico"') {
                    Write-Host "  $linea" -ForegroundColor Red
                } elseif ($linea -match '"timestamp"') {
                    Write-Host "  $linea" -ForegroundColor Green
                } else {
                    Write-Host "  $linea" -ForegroundColor White
                }
            }
        }
    }
    
    Start-Sleep -Seconds 2
}
