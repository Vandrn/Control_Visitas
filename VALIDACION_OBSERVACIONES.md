# 🔍 Validación del Mapeo de Observaciones

## Estructura de Datos - Frontend

### HTML (formulario.blade.php → partials/preguntas.blade.php)

```html
<!-- OPERACIONES (Seccion 2) -->
<textarea name="obs_02_01" placeholder="..."></textarea>
<input type="file" name="IMG_OBS_OPE[]" accept="image/*">

<!-- ADMINISTRACIÓN (Seccion 3) -->
<textarea name="obs_03_01" placeholder="..."></textarea>
<input type="file" name="IMG_OBS_ADM[]" accept="image/*">

<!-- PRODUCTO (Seccion 4) -->
<textarea name="obs_04_01" placeholder="..."></textarea>
<input type="file" name="IMG_OBS_PRO[]" accept="image/*">

<!-- PERSONAL (Seccion 5) -->
<textarea name="obs_05_01" placeholder="..."></textarea>
<input type="file" name="IMG_OBS_PER[]" accept="image/*">
```

---

## Flujo de Captura - Frontend (formulario1.js)

### 1. Subida de Imágenes (setupSubidaIncremental)

```
Usuario selecciona archivo
  ↓
setupSubidaIncremental() detecta input[name^="IMG_"]
  ↓
Identifica fieldName (ej: "IMG_OBS_OPE")
  ↓
Comprime y sube imagen
  ↓
Guarda URL en: imagenesSubidas["IMG_OBS_OPE"] = ["url1", "url2", ...]
```

**Estado después de subida**: `imagenesSubidas = { IMG_OBS_OPE: ["url1"] }`

### 2. Captura de Observación (guardarSeccionActual)

```
Usuario hace click en "Continuar" en seccion-2 (Operaciones)
  ↓
guardarSeccionActual() ejecuta
  ↓
Itera sobre todos los campos: input, select, textarea
  ↓
Encuentra textarea[name="obs_02_01"] con valor
  ↓
MAPEO APLICADO:
  Detecta: rawName = "obs_02_01"
  Busca en mapeoObsImagenes:
    obs_02_01 → IMG_OBS_OPE
  ↓
Obtiene: imagenes = imagenesSubidas["IMG_OBS_OPE"] = ["url1", ...]
  ↓
Crea objeto pregunta:
  {
    codigo_pregunta: "obs_02_01",
    respuesta: "...texto de observación...",
    imagenes: ["url1", ...]
  }
  ↓
Agrega a array preguntas
```

---

## Estructura de Datos - Backend

### Envío HTTP (POST /retail/save-seccion)

```json
{
  "session_id": "UUID",
  "nombre_seccion": "Operaciones",
  "preguntas": [
    {
      "codigo_pregunta": "preg_02_01",
      "respuesta": "5",
      "imagenes": ["url1", "url2"]
    },
    {
      "codigo_pregunta": "preg_02_02",
      "respuesta": "4",
      "imagenes": []
    },
    ...
    {
      "codigo_pregunta": "obs_02_01",
      "respuesta": "Observación de operaciones...",
      "imagenes": ["url_obs1"]
    }
  ]
}
```

### Procesamiento en BigQueryService.actualizarSeccion()

```php
1. Recibe preguntas[]
2. Convierte a JSON:
   $preguntasFormateadas = array_map(function($preg) {
     return [
       'codigo_pregunta' => $preg['codigo_pregunta'],
       'respuesta' => $preg['respuesta'],
       'imagenes' => $preg['imagenes']
     ];
   }, $preguntas);

3. Crea estructura de sección:
   $nuevaSeccion = [
     'nombre_seccion' => 'Operaciones',
     'preguntas' => [ {...}, {...}, {...OBS...}, ...]
   ]

4. Ejecuta MERGE con USING subconsulta
5. Resultado en BigQuery:
   secciones = [
     {
       nombre_seccion: "Operaciones",
       preguntas: [
         {codigo_pregunta: "preg_02_01", respuesta: "5", imagenes: [...]},
         ...
         {codigo_pregunta: "obs_02_01", respuesta: "Observación...", imagenes: [url_obs1]}
       ]
     }
   ]
```

---

## Validaciones Implementadas

### 1. **Observaciones Capturadas Correctamente**
✅ Campo `obs_02_01` se captura con su nombre original
✅ Mantiene el formato consistente con otras preguntas

### 2. **Imágenes Mapeadas Correctamente**
✅ Se buscan en `imagenesSubidas[IMG_OBS_XXX]`
✅ Se incluyen en el array `imagenes` de la pregunta

### 3. **BigQuery Recibe Estructura Correcta**
✅ JSON válido para parsing
✅ Respeta tipos de datos esperados

---

## Rutas de Datos por Sección

| Sección | Textarea | Imagen | MapeoObsImagenes | Destino BQ |
|---------|----------|--------|-----------------|-----------|
| Operaciones | obs_02_01 | IMG_OBS_OPE | obs_02_01→IMG_OBS_OPE | secciones[0].preguntas |
| Administración | obs_03_01 | IMG_OBS_ADM | obs_03_01→IMG_OBS_ADM | secciones[1].preguntas |
| Producto | obs_04_01 | IMG_OBS_PRO | obs_04_01→IMG_OBS_PRO | secciones[2].preguntas |
| Personal | obs_05_01 | IMG_OBS_PER | obs_05_01→IMG_OBS_PER | secciones[3].preguntas |

---

## ✅ Checklist de Implementación

- [x] Mapeo de observaciones a imágenes en mapeoObsImagenes
- [x] Iteración correcta sobre campos en guardarSeccionActual()
- [x] Búsqueda de imágenes en imagenesSubidas[imagenFieldName]
- [x] Inclusión de observaciones en array preguntas
- [x] MERGE con estructura correcta en BigQuery
- [x] Logging de operaciones para debugging

---

## 🧪 Test Cases

### Test 1: Operaciones con Observación e Imagen
```
1. Completar evaluación de Operaciones
2. Escribir observación en obs_02_01
3. Subir imagen a IMG_OBS_OPE[]
4. Hacer click en Continuar
5. Verificar en BigQuery:
   - secciones contiene pregunta con codigo_pregunta="obs_02_01"
   - imagenes array contiene URL de imagen
```

### Test 2: Administración sin Imagen
```
1. Completar evaluación de Administración
2. Escribir observación en obs_03_01
3. NO subir imagen (campo IMG_OBS_ADM vacío)
4. Hacer click en Continuar
5. Verificar en BigQuery:
   - secciones contiene pregunta con codigo_pregunta="obs_03_01"
   - imagenes array está vacío
```

### Test 3: Producto con Observación Opcional
```
1. Completar evaluación de Producto
2. Escribir observación en obs_04_01
3. Opcionalmente subir imagen a IMG_OBS_PRO[]
4. Hacer click en Continuar
5. Verificar en BigQuery según subida o no de imagen
```
