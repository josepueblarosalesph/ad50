# AD+50 — Portal de empleos para mayores de 50 años

Plataforma web (Chile) que conecta **postulantes mayores de 50 años** con **empresas** que buscan candidatos. El diferenciador es un **motor de matching** que evalúa automáticamente a los postulantes contra los criterios de cada búsqueda publicada por una empresa, de modo que el reclutador ve solo candidatos que cumplen el perfil (no publica ofertas abiertas: define criterios y el sistema le entrega los calces).

> Nota: la documentación de convenciones de código y del ecosistema Laravel vive en [AGENTS.md](AGENTS.md) (Laravel Boost guidelines). Este archivo describe **la lógica de negocio y la arquitectura** del proyecto.

## Stack

- **PHP 8.3 / Laravel 13** (backend monolítico)
- **Livewire 4 + Flux UI (free)** — toda la UI dinámica es server-side en PHP; sin framework JS de front. Alpine.js para interacciones puntuales.
- **Laravel Fortify** — autenticación (login, registro, verificación de email, 2FA)
- **Tailwind CSS 4** + **Vite** (bundling de `resources/css` y `resources/js`)
- **Dos motores de base de datos**: producción corre **MariaDB**; **Laravel Cloud** sigue en pie como entorno de pruebas con **PostgreSQL**, igual que el desarrollo local y la suite. Ambos tienen que funcionar (ver *Dos motores de base de datos* más abajo).
- **Pest 4** para tests; **Pint** para formateo; **Larastan/PHPStan** para análisis estático
- **Gemini** (Google AI Studio) o **anthropic-ai/sdk** para leer el CV del postulante y prellenar su ficha; el proveedor se elige por configuración (ver *Autocompletado de la ficha desde el CV*)

Comandos útiles: `composer dev` (levanta servidor + vite + cola), `composer setup` (instalación inicial), `php artisan test --compact`, `vendor/bin/pint --dirty`.

## Dos motores de base de datos

Producción migró a **MariaDB**. **Laravel Cloud** sigue vivo como entorno de pruebas con **PostgreSQL**, que es también lo que se usa en local y en la suite. **Todo cambio tiene que funcionar en los dos**, y hay diferencias que no se ven leyendo: se ven ejecutando.

```
php artisan test                           # PostgreSQL (phpunit.xml)
vendor/bin/pest -c phpunit.mariadb.xml     # MariaDB, el motor de producción
```

Lo que ya nos mordió, para no repetirlo:

- **DDL con SQL crudo.** `ALTER COLUMN ... TYPE ... USING` es exclusivo de Postgres; MySQL/MariaDB usan `MODIFY`. Ramifica con `DB::getDriverName() === 'pgsql'`, como hacen [2026_07_16_000005](database/migrations/2026_07_16_000005_convertir_estado_busquedas_a_procesos.php) y [2026_08_20_000001](database/migrations/2026_08_20_000001_modalidad_trabajo_a_json.php). `ALTER COLUMN ... SET DEFAULT` sí sirve en ambos.
- **El tipo `json` de MariaDB es `longtext` con `CHECK (json_valid(...))`.** Una fila con contenido no-JSON aborta el `ALTER` al convertir una columna: normaliza los datos antes. Y el esquema reporta `longtext`, no `json`, si lo consultas.
- **Acentos dentro de JSON.** `json_encode` los escapa por defecto (`"Inglés"` → `"Inglés"`). Postgres interpreta el JSON y encuentra la fila igual; **MariaDB compara el texto tal cual y no la encuentra**, así que `whereJsonContains` fallaba en silencio para cualquier valor con tilde. Por eso los modelos castean sus columnas JSON con **`json:unicode`** y no con `array`. Si agregas una columna JSON, usa ese cast. Lo cubre [CompatibilidadDeMotoresTest](tests/Feature/CompatibilidadDeMotoresTest.php).
- **Las secuencias son de Postgres.** [SecuenciasPostgres](app/Support/SecuenciasPostgres.php) no hace nada en otros motores y sus tests se saltan solos.

## Roles y actores

El campo `users.role` es un enum: `postulante`, `empresa`, `admin`. Cada usuario tiene a lo más un `Postulante` **o** una `Empresa` asociada (relación `hasOne`). El destino tras login se resuelve en [User::dashboardRouteName()](app/Models/User.php) según el rol y el estado de onboarding/activación.

- **Postulante** — persona 50+ que crea su ficha profesional para ser encontrada.
- **Empresa** — reclutador que crea búsquedas con criterios y revisa candidatos calzados.
- **Admin** — habilita manualmente a las empresas y supervisa la plataforma.

## Modelo de datos

Esquema base en [database/migrations/2026_01_01_000001_create_ad50_schema.php](database/migrations/2026_01_01_000001_create_ad50_schema.php); múltiples migraciones posteriores añaden campos (perfil profesional, onboarding, activación de empresa, precios en UF, etc.).

| Tabla | Modelo | Rol |
|-------|--------|-----|
| `users` | [User](app/Models/User.php) | Cuenta + `role` + `acepta_ley_21719` (consentimiento Ley 21.719 de datos personales) |
| `postulantes` | [Postulante](app/Models/Postulante.php) | Ficha profesional. Campos JSON: `experiencias`, `educaciones`, `idiomas`. Flags: `visible`, `onboarding_completado`, `onboarding_paso` |
| `empresas` | [Empresa](app/Models/Empresa.php) | Datos de empresa + workflow de activación (`estado_activacion`, contactos, `activada_por`) |
| `planes` | [Plan](app/Models/Plan.php) | Planes de suscripción por `audiencia` (postulante/empresa), precios en CLP y UF |
| `busquedas` | [Busqueda](app/Models/Busqueda.php) | Configuración de filtros guardada de una empresa: `criterios` (JSON), `rubro_oculto`. **No tiene estado ni vigencia**: toda búsqueda participa del matching hasta que se elimina |
| `publicaciones` | [Publicacion](app/Models/Publicacion.php) | Oferta laboral publicada en el portal. `estado` recorre la etapa del proceso (`publicada` → `long_list` → `short_list` → `entrevistas` → `pausada`/`cerrada`/`cancelada`); sigue visible mientras esté en `ESTADOS_VISIBLES` y dentro de `vigente_hasta` |
| `busqueda_candidato` | [BusquedaCandidato](app/Models/BusquedaCandidato.php) | **Tabla pivote del match**: `match_score`, `criterios_cumplidos/totales`, `criterios_detalle` (JSON), `estado_match` (cumple/parcial), `favorito`, `contactado_at` |
| `cupones` | [Cupon](app/Models/Cupon.php) | Descuento sobre el precio de un plan: `tipo` (porcentaje/monto), `valor`, vigencia, `max_usos`/`usos`, `uso_unico_por_empresa`, `plan_id` (NULL = cualquiera) |
| `pagos` | [Pago](app/Models/Pago.php) | Cobro en Flow: `amount` es siempre **lo que se cobró** y `descuento` lo que rebajó el `cupon_id`; el precio de lista se reconstruye con `montoBruto()` |

Relaciones clave: `Empresa hasMany Busqueda hasMany BusquedaCandidato belongsTo Postulante`. La pareja `(busqueda_id, postulante_id)` es única.

## El motor de matching — corazón del sistema

Toda la lógica vive en [app/Services/MatchingService.php](app/Services/MatchingService.php). No es scoring difuso: es una evaluación **criterio por criterio** donde un candidato solo aparece si **cumple TODOS** los criterios definidos.

### Criterios evaluados (`evaluar()`)
Definidos por la empresa al crear la búsqueda; cada uno se compara contra los datos de la ficha del postulante:

- **cargo** — coincide si el criterio está contenido en algún cargo/área de sus experiencias o su `cargo_actual` (match por substring, case-insensitive).
- **carrera**, **especialidad**, **industria** (hasta 3), **ciudad** — igualdad exacta normalizada.
- **min_anios** — `anios_experiencia >= valor`.
- **palabra_clave** — busca el término en cargos, responsabilidades y resumen profesional.

Los criterios de selección múltiple (cargo, carrera, especialidad, industria, ciudad) cumplen si **al menos uno** de los valores seleccionados calza.

### Persistencia del match (`guardarCoincidencia()`)
- Si el postulante **incumple algún** criterio evaluado → se **elimina** de `busqueda_candidato`.
- Si cumple todos → `updateOrCreate` con `estado_match = 'cumple'`, `match_score = 100`, y el detalle por criterio en `criterios_detalle`.
- Los resultados que ve la empresa se filtran por `estado_match = 'cumple'` y `postulante.visible = true` (ver [Resultados](app/Livewire/Empresa/Resultados.php)).

### Sincronización (cuándo se recalcula)
- **`sincronizar(Busqueda)`** — recorre todos los postulantes visibles. Se dispara al **crear/editar una búsqueda** (dentro de una transacción, ver [NuevaBusqueda::save()](app/Livewire/Empresa/NuevaBusqueda.php)).
- **`sincronizarPostulante(Postulante)`** — recorre todas las búsquedas (no hay estado que las apague). Se dispara cuando el **postulante actualiza su ficha** o **cambia su visibilidad** (ver [Panel::toggleVisibilidad()](app/Livewire/Postulante/Panel.php)).

El matching es **eager/precalculado**: se materializa en la tabla pivote en cada cambio relevante, no se recalcula en cada lectura.

## Catálogos profesionales

**Los catálogos grandes no se incrustan en el HTML.** El de cargos son 30.000 valores: llevarlos dentro del `x-data` del combobox costaba 733 KB *por instancia*, reenviados en cada respuesta de Livewire (la ficha en onboarding pesaba 1,8 MB, y hasta 9,4 MB con todo lleno). Hoy el combobox recibe `catalogo="cargo"` y lo descarga de [CatalogoController](app/Http/Controllers/CatalogoController.php), una vez por página y cacheado por el navegador; la URL lleva `CatalogosProfesionales::version()` para invalidarla cuando un admin edita los términos. Si agregas un combobox, usa `catalogo=`; `:opciones=` queda solo para listas cortas.

[app/Support/CatalogosProfesionales.php](app/Support/CatalogosProfesionales.php) es la **fuente de verdad** de valores permitidos: carreras (con sus especialidades anidadas), industrias, ciudades/regiones y cargos/áreas. Tanto la ficha del postulante como el formulario de búsqueda validan sus campos contra estos catálogos (`Rule::in(...)`), garantizando que el matching por igualdad exacta funcione. Al añadir opciones, hazlo aquí.

## Flujos de negocio

### Registro ([Auth/Register](app/Livewire/Auth/Register.php))
Un único formulario con selector de tipo (`?tipo=postulante|empresa`). Crea el `User` + su `Postulante` (onboarding_paso 1) o `Empresa` (estado inactiva). Requiere consentimiento Ley 21.719. Tras registro → verificación de email (Fortify).

### Onboarding del postulante
Tras verificar email, el postulante es forzado a completar su ficha antes de acceder al panel: middleware [EnsurePostulanteOnboardingComplete](app/Http/Middleware/EnsurePostulanteOnboardingComplete.php) redirige a `postulante.ficha` mientras `onboarding_completado = false`. La ficha ([Postulante/Ficha](app/Livewire/Postulante/Ficha.php)) captura perfil profesional completo (experiencias, educación, idiomas, CV subido) y al guardar dispara el matching.

### Autocompletado de la ficha desde el CV

El postulante puede subir su CV en PDF y que la ficha se prellene sola: bloque "¿Tienes tu CV en PDF?" en el paso 1 del onboarding y en la sección Currículum del editor. Vive en [ExtractorCv](app/Services/ExtractorCv.php), que orquesta cinco pasos: validación del archivo (magic bytes, 10 MB, 15 páginas, rechazo de PDF con `/JS`, `/Launch`, `/EmbeddedFile`, `/AA` o un `/OpenAction` que sea una acción); lectura con [LectorDeCvClaude](app/Services/LectorDeCvClaude.php), que manda el PDF entero a Claude con salida forzada por JSON Schema; saneamiento de la salida (normalización Unicode, barrido de patrones de inyección, rechazo de HTML, marcadores de rol y URL fuera de lugar); mapeo a los catálogos con [FichaDesdeCv](app/Support/FichaDesdeCv.php); y un registro de auditoría que nunca incluye el contenido del documento.

Tres reglas que sostienen el diseño:

- **Nada del perfil se persiste automáticamente.** La extracción solo escribe en las propiedades del componente Livewire; la persona revisa cada paso y guarda. Lo único que sí se guarda es el archivo en `cv_ruta`. Esa confirmación es lo que convierte un error de lectura en algo corregible y no en un dato falso en la base.
- **Solo se rellena lo que la persona todavía no ha guardado**, comparando contra la BD y no contra el formulario: en pantalla hay valores por defecto (nacionalidad "Chilena", tipo de documento "rut") que no son respuestas suyas.
- **Los catálogos mandan.** Los de lista corta viajan como `enum` en el esquema; los grandes (30.000 cargos, 12.000 empresas) se calzan en PHP por texto normalizado, y lo que no calza cae en "Otros"/"Otra" con el texto original. No hay calce difuso a propósito: un cargo aproximado envenena el matching, y un campo en blanco no.

**Dónde vive el archivo.** El CV se guarda siempre en el disco `local` (`storage/app/private/cvs`), con el nombre del disco fijo en el código —los 16 puntos que lo tocan: guardado, descargas para empresas, comprobación de existencia y el job de lectura—. **`FILESYSTEM_DISK` no lo mueve**: cambiar esa variable a `s3` no lleva los CV a S3, solo hace que Livewire intente subir ahí sus archivos temporales, y adjuntar un CV falla hasta que se define `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local`. Consecuencia a tener presente: los CV viven en el disco del servidor, así que reemplazarlo o escalarlo se los lleva.

**Proveedor intercambiable.** Quién lee el PDF se elige con `EXTRACTOR_CV_PROVEEDOR` (`gemini` por defecto, o `claude`). Los dos implementan [LectorDeCv](app/Services/LectorDeCv.php) y comparten instrucciones y esquema en [EsquemaCv](app/Support/EsquemaCv.php), así que devuelven la misma estructura y nada aguas abajo sabe cuál está activo:

| Proveedor | Clase | API | Credencial |
|---|---|---|---|
| `gemini` | [LectorDeCvGemini](app/Services/LectorDeCvGemini.php) | Interactions API de Google AI Studio, vía `Http::` | `GEMINI_API_KEY` |
| `claude` | [LectorDeCvClaude](app/Services/LectorDeCvClaude.php) | Messages API, vía `anthropic-ai/sdk` | `ANTHROPIC_API_KEY` |

Dos cosas que costaron encontrarse y conviene no volver a romper:

- La Interactions API **no devuelve `candidates`** como el antiguo `generateContent`, sino una lista `steps` con el turno completo; el texto está en el último paso `model_output`.
- **Gemini rechaza el esquema completo (400) si trae un `enum` muy grande.** El de países (~190 valores) tumbaba toda la petición, con el mensaje inútil "Request contains an invalid argument". Por eso `pais` viaja como texto libre y se calza en PHP, igual que cargos y empresas. Si agregas un catálogo largo al esquema, compruébalo antes con `php scripts/probar-extractor-cv.php`.

Ese script hace **una sola petición** contra el proveedor configurado y muestra qué quedaría propuesto en la ficha; sirve para verificar sin gastar la cuota. Sin la api_key del proveedor elegido el bloque no se ofrece y todo el flujo manual sigue igual.

**La lectura corre en la cola, y eso no es opcional.** Medida contra la API real, una extracción tarda entre 4 y 80 segundos según la carga del proveedor, y nginx corta la petición a los 60 (en local, Herd no define `fastcgi_read_timeout`, así que rige el default). Hacerlo dentro de la petición daba un 502 y una pantalla en negro. Por eso [autocompletarDesdeCv()](app/Livewire/Postulante/Ficha.php) guarda el archivo, despacha [LeerCvDelPostulante](app/Jobs/LeerCvDelPostulante.php) y la pantalla consulta el resultado con `wire:poll`; el estado intermedio vive en caché ([EstadoLecturaCv](app/Support/EstadoLecturaCv.php)), no en la base, porque solo sirve durante esos segundos.

Para saber si todo eso está en pie en un servidor —sin ir adivinando pieza por pieza, que es lo difícil aquí porque **todas fallan en silencio**— está [cv:verificar](app/Console/Commands/VerificarExtractorCv.php):

```
php artisan cv:verificar          # credencial, migraciones, cola y disco
php artisan cv:verificar --leer   # además encola una lectura real y la espera
```

El modo `--leer` es el único que demuestra que hay un worker vivo y que el proveedor responde, porque recorre el mismo camino que un CV de verdad. Gasta una petición de cuota.

Consecuencia práctica: **necesitas un worker corriendo** (`php artisan queue:work`, o `composer dev` que ya lo levanta). Sin él la rueda gira sin avanzar; a los 3 minutos la propia pantalla lo advierte.

### Activación de empresa (aprobación manual por admin)
Las empresas **no se autoactivan**. Máquina de estados `estado_activacion`:
1. `inactiva` — recién registrada.
2. `pendiente` — la empresa envió sus antecedentes (razón social, RUT, contactos) vía [Empresa/Activacion](app/Livewire/Empresa/Activacion.php); se marca `datos_enviados_at`.
3. `activa` — un admin la habilita desde [Admin/Empresas](app/Livewire/Admin/Empresas.php) (`activar()`), registrando `activada_at` y `activada_por`.

El middleware [EnsureEmpresaActiva](app/Http/Middleware/EnsureEmpresaActiva.php) bloquea todo el panel de empresa hasta que `estaActiva()`, redirigiendo a la pantalla de activación.

### Ciclo de la empresa
Empresa activa → crea búsqueda con criterios ([NuevaBusqueda](app/Livewire/Empresa/NuevaBusqueda.php)) → el sistema calza candidatos → revisa [Resultados](app/Livewire/Empresa/Resultados.php) (paginados, filtrables por criterio y por favoritos, marcables como favorito) → ve el detalle de un candidato en [Candidato](app/Livewire/Empresa/Candidato.php). El `rubro_oculto` de la búsqueda controla qué información ve el postulante hasta ser contactado.

### Planes / monetización
Los precios se definen en UF (+ IVA) y viven en el código: [PlanSeeder](database/seeders/PlanSeeder.php) es la fuente, y [Admin/Planes](app/Livewire/Admin/Planes.php) solo los muestra. El cobro se hace en CLP con la UF del día ([ValorUf](app/Services/ValorUf.php), cacheada 24 h) a través de **Flow** ([FlowService](app/Services/FlowService.php) + [FlowController](app/Http/Controllers/FlowController.php)). El único checkout es [Empresa/Planes::contratar()](app/Livewire/Empresa/Planes.php); el plan se activa en el webhook de confirmación, nunca en el retorno del navegador. Hoy solo pagan las empresas.

**Cupones de descuento.** Los crea cualquier admin desde [Admin/Cupones](app/Livewire/Admin/Cupones.php) y la empresa los escribe en la pantalla de planes. Todas las condiciones (vigencia, tope de usos, uso único por empresa, plan al que aplica) las resuelve [Cupon::motivoRechazo()](app/Models/Cupon.php) — **juez único**: el checkout y la pantalla tienen que dar el mismo veredicto, si no un cupón se validaría en pantalla y se caería al cobrar. Tres reglas que conviene no romper:

- El descuento se calcula sobre el **CLP con IVA**, que es lo que se cobra, y nunca deja el monto en negativo.
- Los usos se anotan **cuando el cobro se confirma** (en `FlowController::procesar()`), no al iniciarlo: un pago abandonado no gasta cupo. `Cupon::registrarUso()` lleva la condición dentro del `UPDATE` para que dos webhooks simultáneos no se pasen del tope.
- Si el descuento deja el monto bajo el mínimo de Flow (350 CLP), no hay pasarela: se activa el plan de inmediato y se guarda igualmente un `Pago` en estado `pagado` con `amount = 0` y el cupón asociado. Ese `Pago` es lo que cuenta el tope anual de contrataciones, así que **saltárselo permitiría burlar el tope con un cupón del 100%**.

## Rutas

Definidas en [routes/web.php](routes/web.php). Estructura:
- Públicas: `/` (Landing), `/registro`, `/planes`, `/planes/postulantes`.
- Autenticadas (`auth`, `verified`), agrupadas por rol con sus middlewares de gating (`EnsurePostulanteOnboardingComplete`, `EnsureEmpresaActiva`).
- `/dashboard` redirige al panel correcto según rol vía `dashboardRouteName()`.
- Configuración de cuenta en [routes/settings.php](routes/settings.php); auth/2FA gestionados por Fortify + páginas Livewire en `resources/views/pages/`.

## Organización del código

```
app/
  Livewire/          # Componentes de UI por rol: Auth/, Postulante/, Empresa/, Admin/
  Models/            # Eloquent: User, Postulante, Empresa, Busqueda, BusquedaCandidato, Plan
  Services/          # MatchingService (lógica de calce), ExtractorCv (CV → ficha)
  Support/           # CatalogosProfesionales, Rut, EsquemaCv, FichaDesdeCv
  Http/Middleware/   # Gating de onboarding y activación
  Rules/             # RutValido (validación RUT chileno)
  Concerns/          # Traits de validación reutilizables (perfil, password)
  Actions/Fortify/   # CreateNewUser, ResetUserPassword
resources/views/livewire/   # Plantillas Blade de cada componente Livewire (espejan app/Livewire)
database/migrations/        # Esquema base + iteraciones incrementales
database/seeders/           # PlanSeeder, PostulanteSeeder, EmpresaSeeder, BusquedaSeeder (datos demo)
tests/Feature/              # Tests de flujos: matching, activación, favoritos, registro
```

Convención Livewire: cada componente `App\Livewire\X\Y` tiene su vista en `resources/views/livewire/x/y.blade.php`, con `#[Layout('components.layouts.app')]` (panel autenticado) o `components.layouts.marketing` (público).

## Convenciones de dominio

- **Idioma**: el código de dominio (modelos, métodos, campos de BD) está en **español** (`Busqueda`, `Postulante`, `criterios`, `sincronizar`). Mantén esa consistencia.
- **RUT chileno**: validado con [RutValido](app/Rules/RutValido.php) y formateado con [Rut](app/Support/Rut.php).
- **Ley 21.719** (protección de datos personales de Chile): el consentimiento es obligatorio en el registro (`acepta_ley_21719`).
- Al tocar el matching o los formularios, recuerda que dependen de que los valores coincidan con [CatalogosProfesionales](app/Support/CatalogosProfesionales.php).

## Tests

Los flujos críticos están cubiertos en `tests/Feature/`: [PhaseOneMatchingTest](tests/Feature/PhaseOneMatchingTest.php) (motor de matching), [EmpresaActivationWorkflowTest](tests/Feature/EmpresaActivationWorkflowTest.php), [CandidateFavoritesTest](tests/Feature/CandidateFavoritesTest.php), [CustomRegistrationTest](tests/Feature/CustomRegistrationTest.php). Ejecutar: `php artisan test --compact`.

### Base de datos en las pruebas

Los tests corren sobre **PostgreSQL local**, en la base **`ad50testdb`**; para ejecutarlos contra MariaDB, que es el motor de producción, está `phpunit.mariadb.xml`. La conexión está fijada en [phpunit.xml](phpunit.xml) con `force="true"` (`DB_CONNECTION=pgsql`, host `127.0.0.1`, base `ad50testdb`), de modo que la suite **nunca** hereda la conexión productiva del `.env` ni del shell. Los tests usan `RefreshDatabase`. Setup local: PostgreSQL como servicio de Homebrew + `createdb ad50testdb`.

> ⚠️ **Nunca apuntes la suite a la base productiva** de `.env` (host `...pg.laravel.cloud`, base `ad50`): `RefreshDatabase` **elimina y recrea el esquema** y borraría los datos reales.

**Ojo con las columnas `json` en PostgreSQL** (`experiencias`, `educaciones`, `idiomas`, `regiones_interes`, `industrias_interes`, `modalidad_trabajo`, `habilidades`, `criterios`, etc.): el tipo `json` de Postgres **no admite `=` ni `distinct`**. En MariaDB son `longtext` y sí los admiten, así que es fácil escribir una comparación que funciona contra producción y revienta en la suite. No las compares por igualdad en `assertDatabaseHas(...)` ni uses `distinct()->count(...)` sobre ellas; verifícalas a través del modelo (`expect($postulante->fresh())->industrias_interes->toBe([...])`).
