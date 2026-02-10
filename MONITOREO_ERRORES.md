# 📋 Sistema de Monitoreo de Errores Técnicos

## 📌 Descripción

El formulario de "Control Visitas" ahora captura y registra automáticamente todos los errores técnicos en un archivo de logs separado. Los usuarios **nunca ven jerga técnica** en la interfaz, pero tú puedes revisar los errores completos en los logs.

## 📂 Ubicación de Logs

- **Errores Técnicos**: `storage/logs/errores-tecnicos.log`
- **Logs Generales**: `storage/logs/laravel.log`

## 🔍 Información Capturada en Errores Técnicos

Cada error técnico registra:

```json
{
  "metodo": "saveKPIs",           // Endpoint donde ocurrió
  "session_id": "abc123...",       // ID del formulario afectado
  "error_tecnico": "Value of type JSON cannot be assigned to kpis...",  // Error completo
  "ip_usuario": "192.168.1.100",   // IP de quién estaba usando el formulario
  "url": "https://ejemplo.com/retail/save-kpis",
  "timestamp": "2026-01-21T14:30:45+00:00",
  "detalles": {
    "kpis_count": 6,
    "file": "app/Services/BigQueryService.php",
    "line": 520
  }
}
```

## 🖥️ Cómo Monitorear Errores

### Opción 1: Terminal PowerShell (Recomendado)

```powershell
# Script automático en tiempo real
.\monitorear-errores.ps1
```

Este script:
- ✅ Muestra errores a medida que se generan
- ✅ Colorea información importante (método, session_id, IP)
- ✅ Se ejecuta continuamente

### Opción 2: Ver archivo directamente

```powershell
# Ver últimas líneas del archivo
Get-Content storage/logs/errores-tecnicos.log -Tail 50

# Ver en tiempo real
Get-Content storage/logs/errores-tecnicos.log -Wait
```

### Opción 3: Desde Linux/Mac

```bash
# Ver últimas 50 líneas
tail -n 50 storage/logs/errores-tecnicos.log

# Ver en tiempo real
tail -f storage/logs/errores-tecnicos.log
```

## 🎯 Ejemplo de Error Capturado

Cuando un usuario intenta guardar KPIs y ocurre error de tipo STRUCT:

**Lo que ve el usuario**:
```
Hubo un problema técnico. Por favor, contacta al administrador.
```

**Lo que tú ves en los logs**:
```
[2026-01-21 14:30:45] local.ERROR: ⚠️ ERROR TÉCNICO DETECTADO  
{
  "metodo": "saveKPIs",
  "session_id": "550e8400-e29b-41d4-a716-446655440000",
  "error_tecnico": "Value of type JSON cannot be assigned to kpis, which is type ARRAY<STRUCT<codigo_pregunta STRING, valor STRING, variacion STRING>>",
  "detalles": {
    "kpis_count": 6
  },
  "ip_usuario": "192.168.1.100"
}
```

## 🔐 Privacidad

- Los logs **no contienen** datos del formulario (respuestas, imágenes, etc)
- Solo contienen información técnica y de debugging
- Los logs se retienen por **30 días** automáticamente

## 🚨 Tipos de Errores Detectados

El sistema detecta automáticamente errores técnicos cuando contienen:

- `STRUCT`, `JSON` - Errores de tipo de datos
- `INVALID_ARGUMENT`, `SYNTAX_ERROR` - Errores de sintaxis
- `BigQuery`, `SQL`, `query` - Errores de base de datos
- `parameter`, `token` - Errores de parámetros
- `undefined`, `null` - Errores de referencias
- `constraint`, `foreign key` - Errores de integridad

## 📊 Recomendaciones

1. **Revisa los logs diariamente** (o cuando haya reportes de usuario)
2. **Nota los patterns** - Si ves el mismo error varias veces, es un bug recurrente
3. **Contacta al desarrollador** si ves errores de tipo STRUCT, JSON, o BigQuery
4. **Comunícale al usuario** el resultado después de revisar los logs

## ⚙️ Configuración

Los logs técnicos se configuran en: `config/logging.php`

Canal: `technical_errors`
- Ruta: `storage/logs/errores-tecnicos.log`
- Rotación: Diaria
- Retención: 30 días
- Nivel: ERROR

---

**Última actualización**: 21 Enero 2026  
**Versión**: 1.0
