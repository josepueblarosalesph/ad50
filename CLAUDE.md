# AD+50 — Portal de empleos para mayores de 50 años

Plataforma web (Chile) que conecta **postulantes mayores de 50 años** con **empresas** que buscan candidatos. El diferenciador es un **motor de matching** que evalúa automáticamente a los postulantes contra los criterios de cada búsqueda publicada por una empresa, de modo que el reclutador ve solo candidatos que cumplen el perfil (no publica ofertas abiertas: define criterios y el sistema le entrega los calces).

> Nota: la documentación de convenciones de código y del ecosistema Laravel vive en [AGENTS.md](AGENTS.md) (Laravel Boost guidelines). Este archivo describe **la lógica de negocio y la arquitectura** del proyecto.

## Stack

- **PHP 8.4 / Laravel 13** (backend monolítico)
- **Livewire 4 + Flux UI (free)** — toda la UI dinámica es server-side en PHP; sin framework JS de front. Alpine.js para interacciones puntuales.
- **Laravel Fortify** — autenticación (login, registro, verificación de email, 2FA, passkeys)
- **Tailwind CSS 4** + **Vite** (bundling de `resources/css` y `resources/js`)
- **PostgreSQL** como motor de base de datos. La conexión (`DB_CONNECTION=pgsql`, host, base, usuario y contraseña) se define en `.env` y apunta a la instancia gestionada en **Laravel Cloud**.
- **Pest 4** para tests; **Pint** para formateo; **Larastan/PHPStan** para análisis estático

Comandos útiles: `composer dev` (levanta servidor + vite + cola), `composer setup` (instalación inicial), `php artisan test --compact`, `vendor/bin/pint --dirty`.

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
| `busquedas` | [Busqueda](app/Models/Busqueda.php) | Búsqueda de una empresa. `criterios` (JSON), `estado` (activa/pausada/cerrada), `rubro_oculto` |
| `busqueda_candidato` | [BusquedaCandidato](app/Models/BusquedaCandidato.php) | **Tabla pivote del match**: `match_score`, `criterios_cumplidos/totales`, `criterios_detalle` (JSON), `estado_match` (cumple/parcial), `favorito`, `contactado_at` |

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
- **`sincronizarPostulante(Postulante)`** — recorre todas las búsquedas activas. Se dispara cuando el **postulante actualiza su ficha** o **cambia su visibilidad** (ver [Panel::toggleVisibilidad()](app/Livewire/Postulante/Panel.php)).

El matching es **eager/precalculado**: se materializa en la tabla pivote en cada cambio relevante, no se recalcula en cada lectura.

## Catálogos profesionales

[app/Support/CatalogosProfesionales.php](app/Support/CatalogosProfesionales.php) es la **fuente de verdad** de valores permitidos: carreras (con sus especialidades anidadas), industrias, ciudades/regiones y cargos/áreas. Tanto la ficha del postulante como el formulario de búsqueda validan sus campos contra estos catálogos (`Rule::in(...)`), garantizando que el matching por igualdad exacta funcione. Al añadir opciones, hazlo aquí.

## Flujos de negocio

### Registro ([Auth/Register](app/Livewire/Auth/Register.php))
Un único formulario con selector de tipo (`?tipo=postulante|empresa`). Crea el `User` + su `Postulante` (onboarding_paso 1) o `Empresa` (estado inactiva). Requiere consentimiento Ley 21.719. Tras registro → verificación de email (Fortify).

### Onboarding del postulante
Tras verificar email, el postulante es forzado a completar su ficha antes de acceder al panel: middleware [EnsurePostulanteOnboardingComplete](app/Http/Middleware/EnsurePostulanteOnboardingComplete.php) redirige a `postulante.ficha` mientras `onboarding_completado = false`. La ficha ([Postulante/Ficha](app/Livewire/Postulante/Ficha.php)) captura perfil profesional completo (experiencias, educación, idiomas, CV subido) y al guardar dispara el matching.

### Activación de empresa (aprobación manual por admin)
Las empresas **no se autoactivan**. Máquina de estados `estado_activacion`:
1. `inactiva` — recién registrada.
2. `pendiente` — la empresa envió sus antecedentes (razón social, RUT, contactos) vía [Empresa/Activacion](app/Livewire/Empresa/Activacion.php); se marca `datos_enviados_at`.
3. `activa` — un admin la habilita desde [Admin/Empresas](app/Livewire/Admin/Empresas.php) (`activar()`), registrando `activada_at` y `activada_por`.

El middleware [EnsureEmpresaActiva](app/Http/Middleware/EnsureEmpresaActiva.php) bloquea todo el panel de empresa hasta que `estaActiva()`, redirigiendo a la pantalla de activación.

### Ciclo de la empresa
Empresa activa → crea búsqueda con criterios ([NuevaBusqueda](app/Livewire/Empresa/NuevaBusqueda.php)) → el sistema calza candidatos → revisa [Resultados](app/Livewire/Empresa/Resultados.php) (paginados, filtrables por criterio y por favoritos, marcables como favorito) → ve el detalle de un candidato en [Candidato](app/Livewire/Empresa/Candidato.php). El `rubro_oculto` de la búsqueda controla qué información ve el postulante hasta ser contactado.

### Planes / monetización
[Planes](app/Livewire/Planes.php) (empresas) y [Postulante/Planes](app/Livewire/Postulante/Planes.php) muestran los planes de suscripción. Precios en CLP y UF (migraciones recientes). El cobro real / pasarela de pago no está implementado en el código revisado.

## Rutas

Definidas en [routes/web.php](routes/web.php). Estructura:
- Públicas: `/` (Landing), `/registro`, `/planes`, `/planes/postulantes`.
- Autenticadas (`auth`, `verified`), agrupadas por rol con sus middlewares de gating (`EnsurePostulanteOnboardingComplete`, `EnsureEmpresaActiva`).
- `/dashboard` redirige al panel correcto según rol vía `dashboardRouteName()`.
- Configuración de cuenta en [routes/settings.php](routes/settings.php); auth/2FA/passkeys gestionados por Fortify + páginas Livewire en `resources/views/pages/`.

## Organización del código

```
app/
  Livewire/          # Componentes de UI por rol: Auth/, Postulante/, Empresa/, Admin/
  Models/            # Eloquent: User, Postulante, Empresa, Busqueda, BusquedaCandidato, Plan
  Services/          # MatchingService (lógica de calce)
  Support/           # CatalogosProfesionales, Rut (utilidades de dominio)
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

Los tests corren sobre **PostgreSQL local** (mismo motor que producción), en la base **`ad50testdb`**. La conexión está fijada en [phpunit.xml](phpunit.xml) con `force="true"` (`DB_CONNECTION=pgsql`, host `127.0.0.1`, base `ad50testdb`), de modo que la suite **nunca** hereda la conexión productiva del `.env` ni del shell. Los tests usan `RefreshDatabase`. Setup local: PostgreSQL como servicio de Homebrew + `createdb ad50testdb`.

> ⚠️ **Nunca apuntes la suite a la base productiva** de `.env` (host `...pg.laravel.cloud`, base `ad50`): `RefreshDatabase` **elimina y recrea el esquema** y borraría los datos reales.

**Ojo con las columnas `json` en PostgreSQL** (`experiencias`, `educaciones`, `idiomas`, `regiones_interes`, `industrias_interes`, `modalidad_trabajo`, `habilidades`, `criterios`, etc.): el tipo `json` de Postgres **no admite `=` ni `distinct`** (a diferencia de SQLite). No las compares por igualdad en `assertDatabaseHas(...)` ni uses `distinct()->count(...)` sobre ellas; verifícalas a través del modelo (`expect($postulante->fresh())->industrias_interes->toBe([...])`).
