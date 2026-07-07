# CLAUDE.md — Sweet Go

Sistema web de gestión comercial e inventario a la medida para **Sweet Go — Beauty Experts**
(accesorios y herramientas de belleza). Propuesta de MY Tech Solutions, entrega comprometida
**14 de julio de 2026**. Palabra clave para retomar: **SWEETGO**.

## Stack
- Laravel 12 (PHP 8.2) · Blade + Tailwind CSS v3 (Breeze, stack blade) · Alpine.js
- MySQL (XAMPP/MariaDB), BD `sweetgo`
- Spatie Laravel Permission (roles: `admin`, `vendedor`)
- barryvdh/laravel-dompdf (PDF cotizaciones) · maatwebsite/excel (import/export)

## Entorno local (Windows + XAMPP)
1. Arrancar MySQL de XAMPP (puerto 3306). Si no, `artisan` da error de conexión.
   - Arranque manual: `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini`
2. Composer: `php C:\xampp\php\composer.phar ...` (no está en el PATH como `composer`).
3. Servir: `php artisan serve --port=8095` (config de preview en `../.claude/launch.json`, nombre `sweetgo`).
4. Assets: `npm run build` (o `npm run dev` para HMR).

## Credenciales de desarrollo
- Admin: `admin@sweetgo.com` / `password` (creado por `RolesSeeder`).

## Identidad de marca (paleta)
| Uso | Nombre | HEX |
|-----|--------|-----|
| Primario (logo, botones, títulos) | Rosa principal | `#F58CD3` |
| Fondos suaves, hover, tarjetas | Rosa claro | `#F9E9F8` |
| Acentos, enlaces, iconos | Turquesa principal | `#81D1D1` |
| Fondos secundarios | Turquesa claro | `#C3EAEA` |
| Fondo principal | Blanco | `#FFFFFF` |

Definida en `tailwind.config.js` como `sweetgo.{pink, pink-light, turquoise, turquoise-light}`
y como variables CSS `--sweetgo-*` en `resources/css/app.css`.
Logotipo tipográfico en `resources/views/components/brand.blade.php` (reemplazar por PNG/SVG
oficial en `public/img/` cuando el cliente lo entregue).

## Layout
- `resources/views/layouts/admin.blade.php` — shell de admin con sidebar (9 módulos) + topbar.
  Usar con `@extends('layouts.admin')` + `@section('title')` + `@section('content')`.
- `layouts/guest.blade.php` — auth (login) branded. `welcome.blade.php` — landing pública.

## Alcance (9 módulos de la propuesta) y estado
1. Dashboard (métricas + alertas stock) — **HECHO (F6)** con datos reales.
2. Productos (categorías, foto, ref, precio, filtros) — **HECHO (F2)**.
3. Inventario / stock (una bodega, kardex, import Excel) — **HECHO (F2)**.
4. Catálogo público (enlace compartible por WhatsApp, sin login) — **HECHO (F4)**.
5. Clientes (CRM básico + historial) — **HECHO (F3)**.
6. Cotizaciones (estados borrador→enviada→aprobada, PDF, descuenta stock) — **HECHO (F3)**.
7. Garantías (Recibido→En gestión→Resuelto→Cerrado + evidencias) — **HECHO (F5)**.
8. Usuarios y roles (admin/vendedor) — **base lista (F1)**.
9. Reportes / exportables (Excel/PDF) — **HECHO (F6)**.

### Detalle F2 (implementado)
- Modelos: `Categoria`, `Producto`, `MovimientoStock`. Stock en una sola bodega:
  `productos.stock_actual` + `stock_minimo` (alertas); historial completo en `movimiento_stocks`.
- `Producto::registrarMovimiento($tipo, $cantidad, $motivo, $referencia)` — atómico
  (lockForUpdate + transacción); tipos `entrada`/`salida`/`ajuste` (en ajuste, la cantidad ES el
  stock final). Valida stock no-negativo.
- Controladores: `ProductoController` (resource + `importar`/`plantilla`), `CategoriaController`,
  `StockController` (index, kardex, movimiento, movimientos globales).
- Import masivo: `app/Imports/ProductosImport.php` (Maatwebsite, WithHeadingRow). Columnas:
  nombre, referencia, categoria, precio, stock, stock_minimo. Coincide por nombre (crea/actualiza);
  crea categorías al vuelo; registra entrada si viene stock. Plantilla CSV descargable.
- Rutas `productos/plantilla` y `productos/importar` van ANTES del `Route::resource` (evita choque
  con `productos/{producto}`).
- Datos: 57 productos + 11 categorías vía `ProductosSeeder`, stock inicial 0.

### Detalle F3 (implementado)
- Modelos: `Cliente`, `Cotizacion` (tabla `cotizaciones`), `CotizacionItem`.
- `Cliente`: CRM con ficha + historial de cotizaciones. `ClienteController` resource.
- `Cotizacion`: número correlativo `COT-000X` (`siguienteNumero()`), estados
  borrador→enviada→aprobada/rechazada (`estadoBadge()`), `recalcularTotales()`,
  `aprobar()` que descuenta stock vía `Producto::registrarMovimiento('salida', …, $numero)`
  UNA sola vez (flag `stock_descontado`, atómico con lockForUpdate). Ítems congelan
  nombre/referencia/precio al cotizar.
- `CotizacionController`: resource + `estado` (PATCH, captura RuntimeException de stock
  insuficiente y avisa) + `pdf` (DomPDF, vista `cotizaciones/pdf.blade.php`, papel carta,
  marca Sweet Go con tablas/inline CSS por límites de DomPDF).
- Rutas `cotizaciones/{cotizacion}/pdf` y `/estado` van ANTES del resource.
- Form de cotización (`_form.blade.php`): Alpine `cotizacionForm()` con ítems dinámicos y
  totales en vivo. IMPORTANTE: los `<option>` de producto se renderizan con Blade `@foreach`
  (NO `x-for`) para que `x-model` encuentre el valor al pre-cargar (edición); con x-for el
  select quedaba vacío. `$itemsIniciales` se arma en el controlador, no en la vista.
- Dato demo: cliente «Salón Bella Vista» + COT-0001 aprobada; 5 productos con stock inicial 50.

### Detalle F4 (implementado)
- Modelo `EnlaceCatalogo` (tabla `enlace_catalogos`): token aleatorio 10 chars (auto en `creating`),
  título, activo, contador `visitas` + `ultima_visita`. `getUrlAttribute()` → `route('catalogo.publico')`.
- `CatalogoController`: `index` (gestión de enlaces + preview), `crearEnlace`/`toggleEnlace`/
  `eliminarEnlace`, y `publico($token)` PÚBLICO (sin auth) que valida token activo, incrementa
  visitas y renderiza el catálogo.
- Ruta pública `GET /c/{token}` (`catalogo.publico`) va FUERA del grupo `auth` (en web.php arriba).
  Gestión bajo `auth`: `catalogo`, `catalogo/enlaces...`.
- Vista pública `catalogo/publico.blade.php`: página standalone (no admin layout), responsive
  mobile-first, filtro por categoría + búsqueda 100% client-side (Alpine `catalogo()`), botón
  «Pedir por WhatsApp» por producto + botón flotante. Número en `config/sweetgo.php`
  (`SWEETGO_WHATSAPP` en .env, default 573001234567 — CAMBIAR por el real del cliente).
- Nota: en CLI/tinker las URLs usan APP_URL (localhost:8000); en request web usan el host real.

### Detalle F5 (implementado)
- Modelos: `Garantia` (tabla `garantias`), `GarantiaDocumento` (evidencias en disco `public/garantias`).
- `Garantia`: número `GAR-000X`, estados `recibido→en_gestion→resuelto→cerrado` (`ESTADOS`,
  `ABIERTOS`, `estadoBadge()`, `estado_label`, `es_abierta`, `producto_display`). Producto por
  relación o texto libre. Al cerrar setea `fecha_cierre`; reabrir la limpia.
- `GarantiaController`: index (conteos por estado + filtro), create/store (con evidencias múltiples),
  show (stepper + galería + cambio de estado + adjuntar), `estado` (PATCH), `evidencias` (POST), destroy.
- Indicador por cliente: `Cliente::garantiasAbiertas()`; la ficha del cliente muestra alerta ámbar
  «N garantías abiertas» + tabla de garantías. Sidebar «Garantías» activo.
- Evidencias: file upload múltiple (imágenes/PDF, máx 8MB), `es_imagen` para galería vs icono 📄.
- Demo: GAR-0001 (Salón Bella Vista / Cepillo Alpargata) en gestión + 1 evidencia PNG demo.

Fuera de alcance (según propuesta): PDV presencial, facturación electrónica DIAN, multi-bodega.

### Detalle F+ · Listas de precios (ADENDA — no estaba en propuesta original)
- El cliente maneja varios precios por tipo de cliente (Normal/Público, Mayorista, Super Mayorista).
- Modelos: `ListaPrecio` (tabla `lista_precios`; flags `es_publica`, `es_predeterminada`),
  `PrecioProducto` (tabla `precio_productos`, único por producto+lista). `clientes.lista_precio_id`.
- `Producto::precioEnLista($listaId)` → precio de la lista o fallback a `productos.precio`.
  `productos.precio` = precio de la lista PÚBLICA (lo lee el catálogo); se sincroniza al guardar
  la matriz o editar el producto.
- `ListaPrecioController`: gestión de listas + matriz de precios (producto × lista) en
  `listas-precios/` (botón en productos index). Sólo una pública y una predeterminada.
- Cliente: campo «Lista de precios» en el form (default = predeterminada), visible en su ficha.
- Cotización: `datosFormulario()` pasa productos con `precios` por lista + mapa cliente→lista;
  Alpine `cotizacionForm` calcula `listaActiva` según el cliente y repreciar ítems al cambiarlo.
  OJO: en `_form` los `<option>` de producto usan acceso de ARRAY (`$p['id']`) porque
  `$productos` se pasa como colección de arrays, no de modelos.
- Seed `ListasPreciosSeeder`: 3 listas + precio base en cada una (57×3=171 precios).
- Demo: cliente «Salón Bella Vista» = Mayorista; Cepillo Alpargata Normal 8500 / May 7500 / Super 6500.

### Detalle F6 · Dashboard y reportes (implementado)
- `DashboardController@index` (reemplaza el closure): ventas del mes (aprobadas por `aprobada_at`),
  cotizaciones en curso, productos activos, alertas de stock bajo, clientes nuevos del mes,
  garantías abiertas, top 5 más vendidos (CotizacionItem de cotizaciones aprobadas), lista de stock
  bajo, últimas cotizaciones. Vista `dashboard.blade.php` con datos reales.
- `ReporteController` + página `reportes/`: exportables Inventario / Cotizaciones / Clientes en
  Excel y PDF.
- Excel: `app/Exports/{Inventario,Cotizaciones,Clientes}Export.php` (Maatwebsite, FromCollection +
  WithHeadings + WithMapping + **WithStrictNullComparison** — sin este último los 0 salen en blanco).
- PDF: `reportes/pdf/{inventario,cotizaciones,clientes}.blade.php` con marca Sweet Go
  (parcial `_estilos.blade.php`); cotizaciones en horizontal (landscape).

### Bitácora de auditoría (ADENDA — solo rol admin)
- `Bitacora` (tabla `bitacoras`): user_id, accion, modelo, modelo_id, descripcion, cambios(json), ip.
  `Bitacora::registrar($accion, $descripcion, $modelo, $id, $cambios)`. `accionBadge()`.
- Trait `App\Models\Concerns\RegistraBitacora`: auto-loguea created/updated/eliminó SOLO si `Auth::check()`
  (no ensucia con seeders). Modelos pueden definir `protected array $bitacoraIgnorar` (Producto ignora
  `stock_actual`; EnlaceCatalogo ignora `visitas`/`ultima_visita`) y `bitacoraEtiqueta()`.
  Aplicado a: Producto, Cliente, Cotizacion, Garantia, Categoria, ListaPrecio, EnlaceCatalogo.
- Movimientos de stock: log manual dentro de `Producto::registrarMovimiento` (acción `movimiento`).
- Login/Logout: listeners en `AppServiceProvider::boot()` (eventos Login/Logout).
- `BitacoraController@index`: **solo admin** (`abort_unless(hasRole('admin'), 403)`), filtros por
  usuario/acción/módulo/fecha, paginado. Ruta `bitacora`. Sidebar muestra «Bitácora» solo si admin.
- Verificado: vendedor recibe 403 y no ve el enlace; admin ve todo (login, logout, actualizó, movimiento).

### QA integral + hardening (2026-07-06 tarde)
- Se lanzaron 4 agentes de QA (seguridad, código, UI, datos): informe consolidado con 3 críticos, 8 altos, 9 medios, 7 bajos.
- Correcciones aplicadas en 5 tandas + parche de regresión, TODAS verificadas por agentes de regresión con cero medios/altos abiertos:
  * **T1** Seguridad: middleware `role:` en grupo auth + subgrupo `role:admin`. Registro público desactivado. Módulo `UsuarioController` (CRUD con `Password::defaults`, protección del último admin, no auto-eliminación, reasignación de recursos al eliminar). IDOR "cada quien lo suyo": `Cliente.user_id` (migración `add_user_id_to_clientes_table`), scope `visiblesPara`, `autorizarAcceso()` en Cliente/Cotizacion/Garantia. `throttle:40,1` en catálogo público + saneado silencioso de inputs. MIME `jpg,jpeg,png,webp,pdf` en evidencias.
  * **T2** Integridad: `Cotizacion::revertirStock()` reponer stock al rechazar aprobada, `crearConNumero()` con reintento en `errno 1062` (Cotizacion + Garantia). Migración `harden_cliente_and_ventas_constraints`: RESTRICT en cliente_id + UNIQUE en documento. Tope de descuento (subtotal). Items huérfanos → RuntimeException. Cantidad ≥ 1 en entrada/salida.
  * **T3** Bugs: `ProductosImport::parsearPrecio()` maneja formatos COP ("8.500", "8.500,00", "$8,500.75", etc). OR de búsqueda agrupado. Mensajes `error` (rojo) en layout + controllers. Visitas de catálogo en 1 sola query.
  * **T4** UI: perfil rehecho con `layouts.admin` en español; páginas 404/403/419/500 con marca Sweet Go; barra de acciones `flex-wrap` en móvil; badge "Agotado" + botón "No disponible" en catálogo público; texto "Activo/Inactivo" (a11y); botones "Eliminar" solo si `hasRole('admin')`.
  * **T5** Regresión: endurecer `autorizarAcceso` para tratar `user_id=null` como no accesible por vendedores; `UsuarioController::destroy` reasigna Cliente/Cotizacion/Garantia al admin actual antes de eliminar; store de Cotizacion/Garantia valida que `cliente_id` sea `visiblesPara` el vendedor.

### Ronda 2 · Mejoras post-QA (2026-07-06 noche)
- **Bajos del QA cerrados:** listados de cotizaciones y garantías ahora ordenan por `numero` desc;
  `Cotizacion::revertirStock()` registra evento `reversión` en Bitácora (badge naranja);
  ruta `garantias.evidencias` con `throttle:20,1` + validación `evidencias` (array max 10);
  catálogo público verificado sin `{!! !!}`; `garantias.fecha_cierre` unificado a `date`.
- **Componente `<x-file-input>`** con nombre visible sin truncar; aplicado a productos y garantías.
- **Reportes con rango de fechas**: filtro `desde/hasta` en la pantalla, propaga a Excel/PDF de Cotizaciones,
  KPIs "Aprobadas periodo" y "Ventas periodo" en la vista.
- **Módulo Usuarios**: botón "Reset contraseña" (genera temporal aleatoria y la muestra una vez).
- **Comando `sweetgo:demo-clean`** (--force, --reset-admin-password=): borra clientes/cotizaciones/garantías/
  movimientos/enlaces/bitácora, conserva productos/categorías/listas, elimina usuarios de prueba,
  opcionalmente cambia contraseña del admin. Uso previo a producción.
- **Hardening producción**: `URL::forceScheme('https')` cuando `APP_ENV=production`, `session.secure=true`,
  `same_site=lax`; `trustProxies(at: '*')` para hosting con proxy.
- **`PRODUCCION.md`** con guía de despliegue paso a paso (.env, comandos, limpieza, checklist).

### Ronda 3 · Experiencia operativa (2026-07-06 madrugada)
- **Duplicar cotización**: `CotizacionController::duplicar` crea borrador con mismos ítems (precio congelado).
  Ruta `POST cotizaciones/{cotizacion}/duplicar`. Botón en show.
- **Alerta de validez vencida**: `Cotizacion::$esta_vencida` (validez pasada + estado borrador/enviada).
  Badge "Vencida" en listado y show.
- **Centro de notificaciones**: `NotificacionesComposer` registrado en `layouts.admin`. Campanita en topbar
  con contador (stock bajo global + cotizaciones enviadas propias + garantías abiertas propias). Admin ve
  todo, vendedor solo lo suyo. Dropdown con enlaces directos.
- **Búsqueda global**: endpoint `GET /buscar` (`BusquedaController`, JSON) busca productos + clientes +
  cotizaciones + garantías (respetando scope). Input en topbar con debounce 300ms, atajo `/`, dropdown
  agrupado por tipo (mínimo 2 caracteres).
- **Gráfico de ventas en dashboard**: SVG puro con la paleta Sweet Go, ventas por día de últimos 30 días,
  área con gradiente + línea + puntos con tooltip nativo. `groupByRaw('DATE(aprobada_at)')` para pasar
  MariaDB `only_full_group_by`.

### Cierre final del plan QA (2026-07-06 madrugada)
- **BAJOS residuales del plan QA cerrados (100%):** `abort_unless('admin')` en `BitacoraController` como
  defensa en profundidad; `ClienteController::update` auto-adopta clientes huérfanos al editarlos.
- **Regresión final Ronda 2+3 pasada.** Nuevos hallazgos cerrados:
  * MEDIO #3 — `CotizacionController::duplicar` re-valida `visiblesPara` sobre `cliente_id`.
  * MEDIO #4 — Validez fija de 15 días en duplicado (predecible, no hereda vencimiento).
  * BAJO #1 — `/buscar` con `throttle:60,1` + `mb_substr($q, 0, 60)` (tope de longitud).
  * INFO #7 — `sweetgo:demo-clean` ahora borra `storage/app/public/garantias`.
  * MEDIO #5 — Nota en PRODUCCION.md sobre `trustProxies(at: null)` si no hay proxy real.
- **Omitidos por costo/beneficio:** cache en NotificacionesComposer (no urgente), user_agent en Login (cosmético).

## Estado global: QA al 100%. Sin críticos, altos ni medios abiertos.
Rondas: F1–F6 + adendas + QA + 5 tandas + Ronda 2 + Ronda 3 + cierre final. Sistema **listo para producción**.
Pendiente antes de entrega real: cambiar `SWEETGO_WHATSAPP`, reemplazar logo tipográfico por el
oficial, definir APP_URL de producción, y limpiar/mantener o borrar los datos demo.

## Datos de origen
- Lista de precios: `C:\Users\DELL\Downloads\Lista de Productos con Precio - Sweet Go.xlsx`
  (57 productos: item, producto, referencia 4001–4072, precio COP).
- Catálogo con imágenes: `C:\Users\DELL\Downloads\SWEETGO-Catalogo-V7 (3).pdf` (602 págs).
- Propuesta comercial: `C:\Users\DELL\Downloads\Propuesta_Sweet_Go.pdf`.

> Nota: proyecto **nuevo desde cero** (no basado en la rama `miracle` de Santibc/portfolio,
> aunque miracle sirve como referencia de patrones para catálogo público, kardex y cotizaciones).
