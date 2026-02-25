Sistema de Gestión de Inventario Minero - DYM SAC
1. MVP INICIAL (Versión 1.0 - 3 meses)
Objetivo
Sistema web operativo para gestión de inventario en mineras medianas/pequeñas con funcionalidades esenciales que resuelvan los problemas críticos de control de stock y desabastecimientos.
Módulos MVP
1.1 Gestión de Inventario
•	Catálogo de productos por familias mineras (repuestos, herramientas, EPPs, químicos, lubricantes)
•	Control de stock en tiempo real con kardex valorizado
•	Múltiples métodos de valorización (PEPS, Promedio Ponderado)
•	Gestión multialmacén (campamento mina, almacén central, satélites)
•	Alertas automáticas de stock mínimo
•	Trazabilidad por lote y fecha de vencimiento
1.2 Sistema de Requisiciones
•	Solicitudes de materiales por área/centro de costos
•	Flujo de aprobación configurable por niveles (supervisor → jefe → gerencia)
•	Vales de salida con confirmación de contraseña del usuario
•	Historial completo de requisiciones
•	Estados: solicitada, aprobada, rechazada, entregada
1.3 Órdenes de Compra
•	Generación manual de órdenes de compra
•	Sugerencia automática cuando stock alcanza mínimo
•	Seguimiento de estados (solicitada, aprobada, en tránsito, recibida)
•	Comparador básico de cotizaciones (hasta 3 proveedores)
•	Alertas de pedidos retrasados
1.4 Gestión de Proveedores
•	Registro de proveedores con datos completos
•	Historial de compras por proveedor
•	Calificación básica (manual)
•	Datos de contacto y términos comerciales
1.5 Control de EPPs
•	Registro de entrega por trabajador
•	Asignación con confirmación de contraseña del receptor
•	Calendario de renovación por tipo de EPP
•	Alertas de vencimiento próximo
•	Historial completo por empleado
1.6 Dashboard Ejecutivo
•	Valor total de inventario actualizado
•	Indicadores principales: rotación, stock crítico, consumo mensual
•	Top 10 productos más consumidos
•	Alertas críticas destacadas
•	Gráficos básicos de consumo por área
1.7 Reportes Básicos
•	Kardex valorizado por producto
•	Inventario valorizado al cierre
•	Consumo por centro de costos
•	Materiales próximos a agotar
•	Reporte de requisiciones por período
•	Exportación a Excel/PDF
1.8 Administración
•	Gestión de usuarios y roles (administrador, almacenero, jefe área, gerencia)
•	Configuración de centros de costos
•	Configuración de almacenes
•	Parámetros del sistema (stock mínimo global, métodos de costeo)
•	Log de auditoría básico
Características Técnicas MVP
•	Interfaz web responsive (desktop y tablet)
•	Sistema multi-empresa (multi-tenancy)
•	Autenticación segura
•	Respaldos automáticos diarios
•	Exportación de reportes PDF/Excel
Usuarios Objetivo MVP
•	2-3 mineras piloto
•	50-200 trabajadores por empresa
•	5-15 usuarios simultáneos por empresa

Decisiones Técnicas MVP
•	Confirmación de acciones: Las aprobaciones y entregas se confirman con contraseña del usuario (no firma digital)
•	Cache y Colas: Driver database de Laravel (sin Redis en MVP)
•	Almacenamiento: Storage local del servidor (configurable a S3 en producción)
•	Nomenclatura: Código fuente y base de datos en español (tablas: productos, requisiciones, proveedores, etc.)
•	Multi-tenancy: Paquete stancl/tenancy con estrategia de columna empresa_id
•	Validación RUC: Integración con API gratuita de SUNAT para validar proveedores (ver sección 1.9)

1.9 Validación de RUC (SUNAT)
•	Consulta automática al registrar proveedores
•	Validación de formato (11 dígitos numéricos)
•	Verificación de estado del contribuyente (activo/inactivo)
•	Obtención automática de razón social y dirección fiscal
•	API gratuita: apis.net.pe o similar
•	Funcionamiento offline: Si la API no responde, permite registro manual con advertencia
________________________________________
2. PRODUCTO FINAL (Versión 2.0-3.0 - 12-18 meses)
Objetivo
Plataforma integral de gestión de materiales y suministros para operaciones mineras con capacidades predictivas, integraciones avanzadas y optimización de costos.
Módulos Adicionales al MVP
2.1 Asistente Predictivo de Compras (Diferenciador clave)
•	Análisis de patrones de consumo histórico
•	Predicción de necesidades basada en plan de producción minera
•	Sugerencias automáticas: qué comprar, cuánto, cuándo
•	Cálculo de punto de reorden óptimo por producto
•	Recomendación de proveedor (mejor precio/tiempo/calidad)
•	Alertas predictivas de desabastecimiento (2-4 semanas anticipación)
•	Optimización de cantidades de compra (Economic Order Quantity)
2.2 Marketplace de Proveedores Colaborativo
•	Base de datos compartida de precios referenciales entre mineras región
•	Comparativas de tiempo de entrega real por proveedor
•	Sistema de calificación colaborativo
•	Notificaciones de mejores ofertas
•	Historial de incumplimientos compartido
•	Indicadores de confiabilidad por proveedor
2.3 Gestión Avanzada de Compras
•	Solicitudes de cotización automatizadas a múltiples proveedores
•	Comparador avanzado con scoring (precio, tiempo, calidad, riesgo)
•	Negociación electrónica básica
•	Contratos marco con proveedores frecuentes
•	Gestión de anticipos y pagos parciales
•	Evaluación automática de desempeño de proveedores
2.4 Control de Costos y Presupuesto
•	Presupuesto por centro de costos y período
•	Alertas de desviación presupuestaria
•	Análisis de variaciones real vs presupuestado
•	Proyección de consumo trimestral/anual
•	Análisis ABC de inventario (clasificación por valor)
•	Indicadores de eficiencia (costos de almacenamiento, obsolescencia)
2.5 Integración con Planificación Minera
•	Importación de plan de producción mensual
•	Vinculación consumo de materiales con toneladas procesadas
•	Cálculo de ratios de consumo (explosivos/ton, acero/metro perforado)
•	Proyección de necesidades según plan minero
•	Alertas de inconsistencias producción vs materiales
2.6 Gestión de Mantenimiento Básica
•	Registro de equipos críticos (palas, camiones, chancadoras)
•	Programación de mantenimientos preventivos
•	Vinculación repuestos con equipos
•	Alertas de stock de repuestos para mantenimientos programados
•	Historial de consumo de repuestos por equipo
•	Cálculo de disponibilidad mecánica
2.7 Control de Calidad de Materiales
•	Registro de certificados de calidad al ingresar materiales
•	Archivo de MSDS (hojas de seguridad)
•	Control de lotes defectuosos
•	Trazabilidad de reclamos a proveedores
•	Documentación de no conformidades
2.8 Gestión de Activos Menores
•	Control de herramientas asignadas por trabajador
•	Sistema de préstamo/devolución con confirmación de usuario
•	Ubicación de activos en áreas de trabajo
•	Control de obsolescencia y baja de activos
•	Inventario cíclico programado
2.9 Análisis Avanzado e Inteligencia de Negocio
•	Dashboard personalizable por rol
•	Análisis de tendencias de consumo
•	Estacionalidad de productos
•	Correlación producción vs consumo
•	Benchmarking entre unidades mineras (si aplica)
•	Simulaciones de escenarios (qué pasa si...)
•	Exportación a Power BI o Tableau
2.10 Mobile App (Opcional)
•	App móvil para almaceneros (registro entrada/salida rápida)
•	Escaneo de códigos de barras con cámara
•	Inventario físico con tablet en almacén
•	Aprobación de requisiciones desde celular para gerentes
•	Consulta de stock disponible desde terreno
2.11 Integraciones
•	API REST para integración con ERP existentes
•	Conexión con balanzas/básculas para control de despacho
•	Integración con sistemas de facturación electrónica (opcional)
•	Validación de RUC vía API SUNAT (incluido desde MVP)
•	Guías de remisión electrónicas (futuro, si aplica)
•	Webhook para notificaciones a sistemas externos
2.12 Reportes Avanzados
•	Reportes parametrizables por usuario
•	Programación de envío automático de reportes
•	Análisis de rotación por familia de productos
•	Curva ABC de inventario
•	Análisis de obsolescencia (productos sin movimiento)
•	Comparativos período a período
•	Consolidados multi-empresa (para grupos mineros)
Características Técnicas Producto Final
•	Progressive Web App (funciona offline parcialmente)
•	API documentada para integraciones
•	Sistema de notificaciones multi-canal (email, WhatsApp, SMS)
•	Workflows configurables sin código
•	Multi-idioma (español/inglés)
•	Multi-moneda
•	Validación de RUC integrada con SUNAT
•	Certificación ISO 27001 (seguridad información) - opcional
Usuarios Objetivo Producto Final
•	20-50 mineras
•	Hasta 500 trabajadores por empresa
•	30-50 usuarios simultáneos por empresa
•	Grupos mineros con múltiples unidades
________________________________________
3. ARQUITECTURA DEL SISTEMA
3.1 Arquitectura General
Tipo: Arquitectura Monolítica Modular con patrón MVC
┌─────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN                  │
│  ┌──────────────────────────────────────────────────┐  │
│  │         Vue.js 3 + Inertia.js + Tailwind         │  │
│  │  (Interfaz usuario, componentes reactivos)       │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↕ HTTP/HTTPS
┌─────────────────────────────────────────────────────────┐
│                     CAPA DE APLICACIÓN                   │
│  ┌──────────────────────────────────────────────────┐  │
│  │              Laravel 11 (Backend)                │  │
│  │                                                   │  │
│  │  ├─ Controladores (rutas, requests)              │  │
│  │  ├─ Servicios (lógica negocio)                   │  │
│  │  ├─ Repositorios (acceso datos)                  │  │
│  │  ├─ Jobs/Queues (tareas asíncronas)              │  │
│  │  ├─ Events/Listeners (notificaciones)            │  │
│  │  └─ Middleware (autenticación, tenancy)          │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↕ Query/ORM
┌─────────────────────────────────────────────────────────┐
│                    CAPA DE PERSISTENCIA                  │
│  ┌──────────────────────────────────────────────────┐  │
│  │              MySQL 8.0 (Base Datos)              │  │
│  │  - Tablas transaccionales (inventario, órdenes)  │  │
│  │  - Tablas maestras (productos, proveedores)      │  │
│  │  - Tablas auditoría (logs, históricos)           │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │    Cache y Colas (Database driver en MVP)        │  │
│  │    (Redis opcional para producción a escala)     │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │    Almacenamiento Archivos (Storage local)       │  │
│  │    (Migrable a S3 en producción)                 │  │
│  │  - PDFs de vales firmados                        │  │
│  │  - Fotos de EPPs                                 │  │
│  │  - Certificados de materiales                    │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
3.2 Arquitectura de Datos (Multi-tenancy)
Estrategia: Base de datos única con columna empresa_id (Tenant ID)
Ventajas:
- Simplicidad de mantenimiento
- Consultas eficientes
- Costos optimizados
- Migraciones centralizadas

Implementación:
- Middleware global que filtra automáticamente por empresa_id
- Scopes globales en modelos Eloquent
- Aislamiento lógico garantizado por Laravel Tenancy
3.3 Modelo de Capas
Capa de Presentación (Frontend)
•	Componentes Vue.js reutilizables
•	Estado global con Pinia
•	Comunicación con backend via Inertia (sin API REST)
•	Validación cliente + servidor
Capa de Aplicación (Backend)
•	Controladores: Manejan requests HTTP, delegan a servicios
•	Servicios: Lógica de negocio compleja
•	Repositorios: Abstracción acceso a datos
•	Modelos: Entidades de negocio (Eloquent ORM)
•	DTOs: Transferencia de datos entre capas
•	Validadores: Form Requests para validación
Capa de Persistencia
•	MySQL para datos transaccionales
•	Cache y colas con driver database (MVP) / Redis (producción a escala)
•	Sistema de archivos local para documentos (migrable a S3)
3.4 Módulos del Sistema
app/
├── Modules/
│   ├── Inventario/
│   │   ├── Controllers/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Models/
│   │   └── Views/
│   ├── Compras/
│   ├── Almacen/
│   ├── Requisiciones/
│   ├── Proveedores/
│   ├── EPPs/
│   ├── Reportes/
│   └── Administracion/
├── Core/
│   ├── Tenancy/          (Multi-empresa)
│   ├── Authentication/   (Login, permisos)
│   ├── Notifications/    (Email, alertas)
│   └── Auditing/         (Logs, trazabilidad)
└── Shared/
    ├── Helpers/
    ├── Traits/
    └── Constants/
________________________________________
4. PATRONES DE DESARROLLO
4.1 Patrones Arquitectónicos
MVC (Model-View-Controller)
•	Modelo: Eloquent ORM para entidades de negocio
•	Vista: Componentes Vue.js con Inertia
•	Controlador: Clases Laravel que orquestan flujo
Repository Pattern
php
// Abstrae acceso a datos, facilita testing y cambios futuros
interface ProductoRepositoryInterface {
    public function obtenerPorEmpresa($empresaId);
    public function buscarPorCodigo($codigo);
    public function actualizarStock($productoId, $cantidad);
}
Service Layer Pattern
php
// Encapsula lógica de negocio compleja
class RequisicionService {
    public function crearRequisicion(array $datos);
    public function aprobarRequisicion($id, $usuarioId);
    public function generarValeSalida($requisicionId);
}
DTO (Data Transfer Object)
php
// Transferencia tipada de datos entre capas
class CrearProductoDTO {
    public function __construct(
        public string $codigo,
        public string $nombre,
        public float $precioUnitario,
        public int $stockMinimo
    ) {}
}
4.2 Patrones de Diseño (Design Patterns)
Factory Pattern
php
// Para crear diferentes tipos de reportes
class ReporteFactory {
    public static function crear($tipo) {
        return match($tipo) {
            'kardex' => new KardexReporte(),
            'inventario' => new InventarioReporte(),
            'consumo' => new ConsumoReporte(),
        };
    }
}
Strategy Pattern
php
// Para métodos de valorización de inventario
interface MetodoValorizacionInterface {
    public function calcularCosto($movimientos);
}

class PEPS implements MetodoValorizacionInterface { }
class PromedioMobile implements MetodoValorizacionInterface { }
Observer Pattern
php
// Para notificaciones automáticas
// Cuando stock llega a mínimo → envía alerta automática
Event::listen(StockMinimohAlcanzado::class, function($event) {
    // Notificar jefe de almacén
    // Crear sugerencia de orden de compra
});
Singleton Pattern
php
// Para configuración global del sistema
class ConfiguracionSistema {
    private static $instancia;
    
    public static function obtenerInstancia() {
        if (!self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }
}
Builder Pattern
php
// Para construcción compleja de consultas de reportes
class ReporteBuilder {
    public function porEmpresa($id);
    public function porFecha($inicio, $fin);
    public function porAlmacen($almacenId);
    public function conDetalle();
    public function generar();
}
Decorator Pattern
php
// Para agregar funcionalidades a reportes base
$reporte = new ReporteBasico();
$reporte = new ConGraficos($reporte);
$reporte = new ConExportacionPDF($reporte);
4.3 Principios SOLID
S - Single Responsibility Principle
•	Cada clase tiene una única responsabilidad
•	Ejemplo: ProductoRepository solo maneja acceso a datos de productos
O - Open/Closed Principle
•	Clases abiertas a extensión, cerradas a modificación
•	Uso de interfaces para permitir nuevas implementaciones
L - Liskov Substitution Principle
•	Subtipos deben poder sustituir a tipos base
•	Interfaces claras para métodos de valorización
I - Interface Segregation Principle
•	Interfaces específicas mejor que interfaces generales
•	ReporteExportable, Notificable, Auditable separados
D - Dependency Inversion Principle
•	Depender de abstracciones, no de implementaciones concretas
•	Inyección de dependencias via constructor
4.4 Prácticas de Código Limpio
Nomenclatura en Español
php
// Variables, métodos, clases en español
$productosSinStock = Producto::sinStock()->get();
$requisicion->aprobarPorGerencia($usuario);
Funciones Pequeñas y Descriptivas
php
// Máximo 20-30 líneas por método
public function validarStockDisponible($productoId, $cantidadSolicitada) {
    $stockActual = $this->obtenerStockActual($productoId);
    return $stockActual >= $cantidadSolicitada;
}
Evitar Números Mágicos
php
// Usar constantes nombradas
const DIAS_ALERTA_STOCK_MINIMO = 7;
const CANTIDAD_MAXIMA_REQUISICION_SIN_APROBACION = 1000;
Comentarios Significativos
php
// Solo cuando lógica no sea obvia
// Calcula costo promedio ponderado considerando devoluciones
public function calcularCostoPromedioConDevoluciones() { }
```

### 4.5 Arquitectura de Testing

**Unit Tests**
- Pruebas unitarias de servicios y repositorios
- Cobertura mínima 70% en lógica de negocio crítica

**Feature Tests**
- Pruebas de flujos completos (crear requisición → aprobar → generar vale)
- Validación de permisos y multi-tenancy

**Browser Tests (Dusk)**
- Pruebas end-to-end de funcionalidades críticas
- Flujo completo desde interfaz usuario

---

## 5. PATRONES DE DISEÑO DE INTERFAZ (UI/UX)

### 5.1 Principios de Diseño

**Consistencia Visual**
- Sistema de diseño unificado con componentes reutilizables
- Paleta de colores corporativa
- Tipografía coherente en todo el sistema

**Jerarquía Visual Clara**
- Información crítica destacada (alertas de stock bajo)
- Uso de colores semaforizados (rojo=crítico, amarillo=advertencia, verde=ok)
- Tamaños de fuente proporcionales a importancia

**Usabilidad Minera**
- Diseño para tablets industriales (botones grandes, táctil)
- Alto contraste para visibilidad en almacenes con poca luz
- Fuentes legibles desde 1 metro de distancia

### 5.2 Patrones de Navegación

**Navegación Principal (Sidebar)**
```
├── Dashboard
├── Inventario
│   ├── Productos
│   ├── Movimientos
│   └── Kardex
├── Almacén
│   ├── Requisiciones
│   ├── Vales de Salida
│   └── Ingresos
├── Compras
│   ├── Órdenes de Compra
│   ├── Proveedores
│   └── Cotizaciones
├── EPPs
│   ├── Asignaciones
│   └── Vencimientos
├── Reportes
└── Configuración
```

**Breadcrumbs en cada página**
```
Inicio > Inventario > Productos > Editar Producto
```

**Búsqueda Global**
- Buscador omnipresente en header
- Búsqueda por código, nombre producto, proveedor
- Resultados categorizados

### 5.3 Componentes de UI

**Componentes Reutilizables Vue**
```
components/
├── KardexProducto.vue
├── TablaInventario.vue
├── FormularioRequisicion.vue
├── SelectorAlmacen.vue
├── AlertaStockBajo.vue
├── GraficoConsumo.vue
├── FirmaDigital.vue
└── ExportadorExcel.vue
```

**Sistema de Notificaciones**
- Toast notifications (esquina superior derecha)
- Alertas inline en formularios
- Modal para confirmaciones críticas

**Indicadores Visuales**
```
Stock crítico:    🔴 Texto rojo, icono advertencia
Stock bajo:       🟡 Texto amarillo
Stock normal:     🟢 Texto verde
Stock excedente:  🔵 Texto azul
```

### 5.4 Patrones de Formularios

**Validación en Tiempo Real**
- Feedback inmediato al escribir
- Mensajes de error específicos en español
- Campos requeridos marcados con asterisco

**Autocompletado Inteligente**
- Selector de productos con búsqueda tipo-ahead
- Autocompletar proveedores basado en historial
- Sugerencias de stock mínimo basado en consumo histórico

**Flujos Multi-paso**
- Wizards para procesos complejos (crear requisición con múltiples productos)
- Indicador de progreso visible
- Posibilidad de guardar borrador

### 5.5 Responsive Design

**Breakpoints**
```
Desktop:  1280px+ (principal)
Tablet:   768px-1279px (almaceneros)
Mobile:   <768px (consultas rápidas, aprobaciones)
Prioridades por Dispositivo
•	Desktop: Dashboards completos, reportes detallados
•	Tablet: Ingreso/salida materiales, inventario físico
•	Mobile: Aprobaciones, consultas stock, notificaciones
________________________________________
6. STACK TECNOLÓGICO COMPLETO
6.1 Backend
Componente	Tecnología	Versión	Propósito
Framework	Laravel	11.x	Framework principal backend
Lenguaje	PHP	8.3	Lenguaje de programación
ORM	Eloquent	Incluido	Mapeo objeto-relacional
Multi-tenancy	Stancl/Tenancy	3.x	Gestión multi-empresa
Autenticación	Laravel Sanctum	Incluido	Auth + tokens API
Permisos	Spatie Permission	6.x	Roles y permisos
Excel	Maatwebsite Excel	3.x	Importar/exportar Excel
PDF	DomPDF	2.x	Generación PDFs
Colas	Laravel Queue	Incluido	Trabajos asíncronos
Cache	Redis	7.x	Cache y sesiones
Validación	Form Requests	Incluido	Validación formularios
Auditoría	Laravel Auditing	13.x	Log de cambios
6.2 Frontend
Componente	Tecnología	Versión	Propósito
Framework	Vue.js	3.x	Framework JavaScript
Bridge	Inertia.js	1.x	Conecta Laravel-Vue sin API
CSS Framework	Tailwind CSS	3.x	Utilidades CSS
Componentes UI	PrimeVue	3.x	Biblioteca componentes
Iconos	Heroicons	2.x	Iconos SVG
Gráficos	Chart.js	4.x	Gráficos interactivos
Tablas	TanStack Table	8.x	Tablas avanzadas
Formularios	VeeValidate	4.x	Validación formularios
Estado	Pinia	2.x	Gestión estado global
Fechas	Day.js	1.x	Manejo fechas ligero
Notificaciones	Vue Toastification	2.x	Notificaciones toast
6.3 Base de Datos
Componente	Tecnología	Versión	Propósito
Motor	MySQL	8.0	Base de datos principal
Cache	Redis	7.x	Cache y colas
Búsqueda	MySQL Full-text	Nativo	Búsqueda texto completo
Backup	mysqldump	Nativo	Respaldos automáticos


Las migraciones y seeders se ejecutaron correctamente. La base de datos está lista con:

Roles creados: 8 roles con 74 permisos totales

super_admin (74 permisos)
administrador (45 permisos)
jefe_almacen (31 permisos)
almacenero (15 permisos)
jefe_area (9 permisos)
gerencia (23 permisos)
compras (15 permisos)
auditor (25 permisos)
Datos demo creados:

Empresa: DYM SAC
5 Centros de costo
3 Almacenes
7 Familias de productos
Usuarios de acceso:

Usuario	Email	Password	Rol
Administrador	admin@dym.com.pe	admin123	super_admin
Juan Pérez	jefe.almacen@dym.com.pe	jefe123	jefe_almacen
María García	almacenero@dym.com.pe	almacen123	almacenero
Carlos López	compras@dym.com.pe	compras123	compras
Ana Torres	gerencia@dym.com.pe	gerencia123	gerencia
Ya puedes probar el login en el frontend con cualquiera de estos usuarios.


Cuando se da salida a un equipo (lámpara, radio, etc.):

Vale de Salida → Se descuenta del stock permanentemente
Movimiento tipo SALIDA → Se registra en el Kardex
El stock baja → Se considera consumido/entregado
El problema: El sistema trata estos equipos igual que materiales consumibles. No hay:
************************
No, actualmente NO se descuenta del stock.

Revisé el controlador EppController.php y el método asignacionesStore() (línea 170-209) solo registra:

Trabajador que recibe
Tipo de EPP
Cantidad, talla, fecha
Fecha de vencimiento (calculada automáticamente)
Pero no hay lógica para descontar del inventario.

Lo que existe:
El modelo TipoEpp tiene un campo producto_id que permite vincular un tipo de EPP con un producto del inventario, pero ese vínculo no se usa.

Lo que falta implementar:
Cuando se asigna un EPP, debería:

Verificar si el tipo de EPP tiene producto_id vinculado
Verificar stock disponible
Crear un movimiento de SALIDA
Descontar del stock_almacen
Registrar en el Kardex
¿Quieres que implemente esta funcionalidad para que al asignar un EPP se descuente automáticamente del stock?