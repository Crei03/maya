# ISSUE: Importador Masivo WMS (Excel / CSV) con Mapeo Inteligente de Columnas

**Tipo:** Feature / Backlog (Futuro Lejano)  
**Módulo:** Paquetes / Despacho de Última Milla (`Admin/Paquetes`)  
**Prioridad:** Baja (Planificado para fase posterior a estabilización de API e ingesta guiada)  

---

## 1. Contexto y Justificación

Actualmente, muchas empresas que operan bodegas y centros de distribución con un **WMS (Warehouse Management System)** o ERP (SAP, WMS Manhattan, Infor, Odoo, Softland, etc.) no cuentan con una integración por API en tiempo real hacia su operador de última milla.

Como práctica operativa habitual, los supervisores de despacho generan un reporte diario en **Excel o CSV** con los pedidos o LPNs preparados para entrega. Para evitar que el operador registre paquete por paquete de forma manual, se requiere un **asistente de importación por lotes** que transforme ese archivo tabular en envíos listos para despacho en Maya.

---

## 2. Historias de Usuario

* **HU-01:** Como despachador de bodega, quiero arrastrar o seleccionar un archivo `.xlsx` o `.csv` exportado desde mi WMS para cargar decenas o cientos de entregas en un solo paso.
* **HU-02:** Como usuario, quiero que el sistema reconozca automáticamente los encabezados de mi archivo (auto-mapping) para no tener que renombrar las columnas de mi archivo WMS cada día.
* **HU-03:** Como usuario, si mi archivo tiene nombres de columna no estándar, quiero un mapeador visual interactivo que me permita vincular cada columna de mi archivo con los campos de Maya y recordar mi plantilla para futuras cargas.
* **HU-04:** Como despachador, quiero una pantalla de validación previa (Dry Run) donde pueda ver los errores o celdas faltantes en rojo y editarlas directamente en una tabla interactiva antes de confirmar la creación definitiva.

---

## 3. Especificación Funcional

### A. Wizard de Importación en 3 Pasos

1. **Paso 1: Carga de Archivo y Selección de Bodega**
   * Selector de Bodega de Origen (por defecto la bodega asignada al usuario o la principal).
   * Dropzone para archivos `.xlsx`, `.xls` y `.csv` (hasta 10 MB).
   * Opción de descargar "Plantilla Modelo" opcional.

2. **Paso 2: Mapeo Inteligente de Columnas (Smart Column Mapping)**
   * Detección por sinónimos comunes:
     * `LPN / Pallet / Matrícula` $\rightarrow$ `lpn_code`
     * `Pedido / Factura / Remisión / SO / Delivery` $\rightarrow$ `reference_number`
     * `Tipo Documento` $\rightarrow$ `reference_type`
     * `Cliente / Destinatario / Consignatario` $\rightarrow$ `recipient_name`
     * `Teléfono / Celular / Móvil` $\rightarrow$ `recipient_phone`
     * `Dirección / Destino / Ubicación` $\rightarrow$ `destination_address`
     * `Corregimiento / Zona / Distrito` $\rightarrow$ enriquecimiento de dirección o geocodificación
     * `Bultos / Piezas / Cajas` $\rightarrow$ `pieces_count`
     * `Peso / Peso Lbs / Peso Kg` $\rightarrow$ `weight_lb`
     * `Notas / Observaciones / Contenido` $\rightarrow$ `content_description`
   * Memoria de Mapeo: Guardar el perfil de mapeo por tenant para no volver a configurarlo.

3. **Paso 3: Previsualización, Validación y Corrección en Celda**
   * Validación de campos requeridos: Destinatario, Dirección, Peso.
   * Resaltado de filas inválidas.
   * Conteo de filas: *Total a importar (X), Válidos (Y), Con advertencias o errores (Z)*.
   * Opción de omitir filas con error o corregir el valor en la celda.
   * Botón de confirmación: *"Importar X Envíos a Bodega"*.

---

## 4. Requerimientos Técnicos

1. **Procesamiento de Archivos:**
   * Backend con `maatwebsite/excel` o `spatie/simple-excel` para procesamiento streaming de bajo consumo de memoria.
   * Para lotes mayores a 500 filas, encolar en un Job de Laravel (`ImportWmsShipmentsJob`) con notificación vía WebSocket o polling de estado.
2. **Auditoría:**
   * Registrar en `audit_logs` la cantidad de envíos importados, nombre del archivo y usuario que ejecutó la carga.
3. **Idempotencia y Duplicados:**
   * Validar si un `lpn_code` o `reference_number` ya fue importado en los últimos N días para advertir al usuario sobre posibles dobles despachos.

---

## 5. Criterios de Aceptación (DoD)

- [ ] Soporte de archivos `.xlsx` y `.csv`.
- [ ] Detección automática de al menos el 80% de las columnas comunes de WMS.
- [ ] Mapeador visual con memoria por tenant.
- [ ] Previsualización con tabla editable antes de inserción en base de datos.
- [ ] Creación de envíos con `status = in_warehouse` y vinculados al lote de importación.
- [ ] Pruebas unitarias y de integración para archivos con datos correctos, parciales y con errores de formato.
