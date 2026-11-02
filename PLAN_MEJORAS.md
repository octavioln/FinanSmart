# Plan de mejoras — FinanSmart

> Análisis realizado el **2026-11-02** sobre el estado actual del repositorio.
> Alcance acordado: **mantener los datos globales** (sin `user_id`) por ahora. El multi-usuario queda diseñado pero **no se implementa en este plan**.

## 0. Estado de implementación (2026-11-02)

**Fases 0 y 1 implementadas.** Cambios aplicados:

- Edición de `Abono` ahora revierte y reaplica el efecto correcto, incluso al cambiar de cuenta.
- Las operaciones de dos asientos (retiro, ahorro, transferencia) comparten `transaction_group_id`; editarlas o eliminarlas afecta a todo el grupo.
- Validación de montos (`numeric`, `decimal:0,2`, `gt:0`, máximo), cuentas/categorías/suscripciones existentes y activas, categorías internas bloqueadas en compras y `payment_day` entre 1 y 31.
- Operaciones multi-asiento envueltas en `DB::transaction()` con `lockForUpdate` y actualización atómica de saldos.
- Los sobregiros se bloquean en compras, retiros, ahorros, transferencias, pagos de suscripción y ediciones que dejarían saldo negativo.
- `Subscription::getAll()` filtra suscripciones, cuentas y categorías borradas.
- Dashboard ya tiene `order()` y ordena por columna con whitelist.
- Corregido el typo `suscriptions` en el `down()` de la migración.
- Registro público deshabilitado; `.env.example` ya no trae una `APP_KEY` concreta ni `APP_DEBUG=true`.
- Nueva migración: `2026_11_02_000000_add_transaction_groups_to_transactions_table.php`.
- **Fase 1:** nuevas páginas `Cuentas` y `Categorías` con crear/editar/archivar/restaurar.
- **Fase 1:** `opening_balance` por cuenta, comando `finance:reconcile [--fix]` y pruebas de reconciliación.
- **Fase 1:** se eliminaron los IDs fijos. `Account::cash()` / `Account::savings()` resuelven las cuentas por flags y `Category::SYSTEM_*` resuelve las categorías internas por `system_key`.
- **Fase 1:** seeders idempotentes, `DatabaseSeeder` siembra cuentas, categorías y (si hay variables de entorno) el usuario administrador.
- **Fase 1:** relaciones Eloquent, casts, precisión monetaria unificada en `decimal(12,2)` e índices de consulta.

**Pendiente:** fases 2–5 (suscripciones con historial, reportes/filtros/localización, multi-usuario y CI).

## 1. Resumen ejecutivo

La aplicación cubre el flujo feliz: registrar compras, retiros, depósitos, ahorro, transferencias, suscripciones y ver saldos. La suite actual pasa (**67 pruebas, 7 omitidas, 161 aserciones**). `composer audit` no reporta vulnerabilidades y las dependencias de producción de npm están limpias; el toolchain de desarrollo acumula 18 avisos (1 crítico, 11 altos, 6 moderados), principalmente por Vite 4, Tailwind y `axios`.

Sin embargo, hay **fallos de integridad contable** que pueden descuadrar los saldos con datos reales, además de validaciones débiles y funcionalidad faltante. Lo más urgente no es agregar pantallas nuevas, sino **proteger los saldos y las operaciones de dos asientos**.

Prioridad recomendada:

1. **Fase 0:** corregir bugs que corrompen datos y cerrar el acceso público.
2. **Fase 1:** formalizar el modelo contable y agregar CRUD de cuentas/categorías.
3. **Fase 2:** completar suscripciones (historial, vencimientos, pagos).
4. **Fase 3:** reportes, filtros, UX y localización.
5. **Fase 4:** multi-usuario (cuando se solicite).

## 2. Lo que ya está bien

- Separación correcta de componentes Livewire de página completa y rutas que los enlazan.
- Uso de `SoftDeletes` en cuentas, categorías, transacciones y suscripciones.
- Tests de los flujos principales y de los cálculos de saldo en el caso `Cargo`.
- Validación de login con rate limiting y 2FA disponible.
- `composer audit` sin avisos; dependencias de producción de npm limpias.
- `AGENTS.md` actualizado con el entorno real.

## 3. Hallazgos

### 3.1 Críticos (pueden corromper datos o exponer información)

| ID | Hallazgo | Evidencia | Impacto |
|---|---|---|---|
| C1 | Al editar una transacción `Abono`, el ajuste de saldo se hace como si fuera `Cargo`. | `app/Http/Livewire/Transactions.php:191-223` | El saldo de la cuenta queda mal; empeora si se cambia de cuenta. |
| C2 | Las operaciones de dos asientos (retiro, ahorro, transferencia) se editan/eliminan por separado. | `Transactions.php:225-379` y `destroy()` en `387-404` | Se modifica un solo lado; el asiento contrario queda huérfano y los saldos dejan de cuadrar. |
| C3 | Validación insuficiente: `amount` solo es `required`; no se valida numérico, positivo, decimales ni IDs existentes. `payment_day` tampoco. | `Transactions.php:59-69, 154-379`; `Subscriptions.php:38-47, 110-155` | Montos negativos, cero, textos o IDs manipulados pueden generar errores 500 o datos corruptos. |
| C4 | Las operaciones multi-asiento no son atómicas y el saldo se actualiza con read-modify-write. | `Transactions.php:225-379`; `app/Models/Account.php:33-47` | Un fallo a mitad deja una transacción sin su contrapartida; dos peticiones concurrentes pueden perder actualizaciones. |
| C5 | El registro público está abierto y los datos financieros son globales. | `config/fortify.php:135`; `routes/web.php:23-31` | Cualquier visitante puede crear una cuenta y ver/editar/eliminar toda la información financiera. |
| C6 | `Subscription::getAll()` no filtra `deleted_at`. | `app/Models/Subscription.php:28-41` | Las suscripciones borradas siguen visibles; al intentar editarlas o borrarlas otra vez, `find()` devuelve `null` y falla. |
| C7 | El dashboard llama a `order()` en las cabeceras, pero el componente no tiene ese método. | `resources/views/livewire/dashboard.blade.php:42,48,53`; `app/Http/Livewire/Dashboard.php` | Al hacer clic en una cabecera se produce un error de Livewire. |
| C8 | La migración de suscripciones elimina `suscriptions` (typo) en `down()`. | `database/migrations/2023_09_13_172017_create_subscriptions_table.php:33` | El rollback no elimina la tabla real y deja la migración inconsistente. |

### 3.2 Altos (funcionalidad faltante o riesgo importante)

| ID | Hallazgo | Evidencia | Impacto |
|---|---|---|---|
| H1 | No hay CRUD de cuentas ni de categorías; solo existen los seeders. | `app/Http/Livewire`; `database/seeders` | No se puede agregar una cuenta nueva, corregir un saldo inicial, renombrar/archivar cuentas ni administrar categorías. |
| H2 | Los saldos son denormalizados y no hay saldo inicial ni reconciliación. | `accounts.available_amount`; `Account::updateAvailableAmount()` | El saldo puede desviarse de la suma real de transacciones sin forma de detectarlo. |
| H3 | `saveWithdraw` y `saveSavings` hardcodean las cuentas 1 y 3; el servidor no valida los flags de la cuenta. | `Transactions.php:225-317` | Si se reordenan/eliminan cuentas, las operaciones fallan o usan la cuenta equivocada. Los flags solo se aplican en la UI. |
| H4 | `saveTransfer` permite misma cuenta de origen/destino y no valida fondos suficientes. | `Transactions.php:319-353` | Se pueden crear transferencias inválidas o dejar saldos negativos. |
| H5 | `saveSubscription` no guarda historial, periodo pagado ni evita pagos duplicados. | `Transactions.php:355-379` | No se sabe qué mes está pagado; se puede pagar dos veces la misma suscripción. |
| H6 | Las consultas raw de listados incluyen cuentas/categorías borradas. | `app/Models/Transaction.php:29-57`; `Subscription.php:28-41` | Se muestran movimientos de cuentas/categorías que ya no existen lógicamente. |
| H7 | La precisión monetaria es inconsistente: `transactions.amount` es `decimal(8,2)` y `accounts.available_amount` es `decimal(12,2)`; además se opera con floats. | Migraciones; `Account::updateAvailableAmount()` | Montos grandes se rechazan/truncan y los redondeos pueden generar diferencias de centavos. |
| H8 | Las categorías de sistema (8 Ahorro, 18 Traspaso, 19 Depósito) se pueden elegir en el formulario de compra. | `Transactions::render()` usa `Category::pluck()` sin filtrar | Se contaminan los reportes con categorías que deberían ser internas. |
| H9 | Los seeders no son idempotentes, `DatabaseSeeder` está vacío y los IDs están hardcodeados. | `database/seeders`; `Transactions.php` | Un `migrate --seed` no deja la app lista; reejecutar seeders duplica datos; insertar filas antes rompe las operaciones. |
| H10 | `User` no implementa `MustVerifyEmail`, pero las rutas usan el middleware `verified`. | `app/Models/User.php`; `routes/web.php:23-31` | Hoy la verificación es un no-op; si se activa, el middleware no funcionará como se espera. |
| H11 | La app está en inglés a nivel de locale/timezone y la moneda está hardcodeada. | `config/app.php:73,86`; vistas con `$` y `number_format()` | Mensajes de validación en inglés, fechas en UTC y formato de moneda no configurable. |
| H12 | `.env.example` incluye una `APP_KEY` concreta y `APP_DEBUG=true`. | `.env.example:3-4` | Riesgo si se usa tal cual en producción; la clave debería generarse por entorno. |
| H13 | No hay policies ni uso real de Spatie Permission; cualquier usuario autenticado puede borrar todo. | `app/Providers/AuthServiceProvider.php`; `config/jetstream.php` | Aceptable mientras sea personal/global, pero es un riesgo si se comparte la app. |

### 3.3 Medios (deuda técnica y funcionalidad incompleta)

| ID | Hallazgo | Evidencia | Impacto |
|---|---|---|---|
| M1 | Sin filtros por fecha, cuenta, categoría o tipo; sin totales mensuales ni reportes. | Listados actuales | La app no responde preguntas básicas: cuánto gasté este mes, en qué, por cuenta. |
| M2 | El dashboard muestra solo 5 movimientos y no tiene paginación visible. | `Transaction::getMostRecent()` con `paginate(5)` | Se pierden movimientos recientes si hay más de 5. |
| M3 | No hay exportación CSV/PDF ni respaldo desde la UI. | — | Difícil migrar o auditar la información. |
| M4 | Faltan `wire:key` en filas, estados `wire:loading` y botones deshabilitados. | Vistas Livewire | Posibles problemas de diffing y doble envío por doble clic. |
| M5 | No hay relaciones Eloquent ni casts; se consulta con query builder. | Modelos | Código más frágil y difícil de reutilizar; los decimales/booleanos/fechas llegan como strings/ints. |
| M6 | El ordenamiento no tiene whitelist de columnas; la búsqueda no escapa `%`/`_`. | `Transaction::getAll()`; `Subscription::getAll()` | Valores manipulados pueden provocar errores SQL; búsquedas imprecisas. |
| M7 | Sin índices explícitos en claves foráneas ni columnas de búsqueda; se usa `LIKE '%texto%'`. | Migraciones; consultas | Degradación con muchos registros. |
| M8 | No hay auditoría de cambios (quién/cuándo). | — | Necesario para multi-usuario y para rastrear descuadres. |
| M9 | `UserFactory` referencia `App\Models\Team`, que no existe; equipos está deshabilitado. | `database/factories/UserFactory.php:5,61-71` | Latente: fallará si se habilitan equipos. |
| M10 | Quedó código Jetstream sin usar (teams, API tokens, welcome, dashboard view). | `resources/views`, `app/Providers` | Ruido y mantenimiento innecesario; el README promete funciones que no están activas. |
| M11 | Dependencias JS por CDN sin SRI: Flowbite, FontAwesome, Moment, Pikaday, SweetAlert2. | `resources/views/layouts/app.blade.php` | Riesgo de disponibilidad/seguridad y carga más lenta. |
| M12 | Detalles de vistas: `seledted` en vez de `selected`, `<h2>` cerrado con `</h1>`, `wire:model` en botones, `wire:click` duplicado en cancelar, montos como `type="text"`. | Vistas de transacciones/suscripciones/dashboard | UX inconsistente y posibles comportamientos raros. |
| M13 | La ruta raíz se llama `/login`; el nombre es confuso. | `routes/web.php:16-21` | Mantenimiento y generación de URLs confusa. |
| M14 | No hay CI ni scripts `composer test/lint`; Pint falla en 24 archivos. | `composer.json`; `vendor/bin/pint --test` | La calidad no se verifica automáticamente y el estilo no es consistente. |
| M15 | No hay restauración de registros soft-deleted. | Modelos/vistas | Un borrado accidental no se puede deshacer desde la UI. |
| M16 | Toolchain JS desactualizado: Vite 4, Tailwind 3, `axios` viejo y otras dependencias de desarrollo. | `package.json`; `npm audit` | 18 avisos de seguridad en desarrollo (1 crítico, 11 altos). Producción está limpia, pero conviene actualizar para evitar deuda y problemas de build. |

### 3.4 Bajos / higiene

- `$validatedData` se asigna y no se usa en varios métodos (`saveWithdraw`, `saveDeposit`, `saveSavings`, `saveTransfer`, `saveSubscription`).
- `rules()` duplica las reglas escritas inline en `save()`/`update()`.
- `use withPagination;` con minúscula inicial (funciona, pero no es consistente).
- Modelos con `$guarded` y `$fillable` a la vez (redundante).
- Comentario de `getMostRecent()` dice “últimas 10” pero pagina 5.
- `x-card` tiene un SVG por defecto que se escaparía con `{{ $logo }}`.
- `x-input` usa `{!! $attributes->merge(...) !!}`; funcionaría con `{{ }}`.
- No hay `wire:key` en `@foreach` de tablas.
- README desactualizado: menciona 67 pruebas/148 aserciones, roles/permisos y API tokens que no están activos.
- `.env.example` usa base `finanzas` y README `finansmart`; APP_NAME distinto.

## 4. Plan de trabajo

### Fase 0 — Contención y integridad (prioridad máxima, 1-2 días)

**Objetivo:** que ninguna operación pueda dejar saldos o asientos inconsistentes.

1. **Corregir la edición de transacciones (C1).**
   - Revertir el efecto anterior según `$tr->type`: si era `Cargo`, aplicar `Abono`; si era `Abono`, aplicar `Cargo`.
   - Aplicar el nuevo efecto según el tipo real (no asumir `Cargo`).
   - Validar que el tipo no se pueda alterar desde el formulario.

2. **Definir y proteger las operaciones de dos asientos (C2).**
   - Opción recomendada: agregar `transaction_group_id` (o `operation_id`) a `transactions`; ambas filas comparten grupo.
   - Editar/eliminar opera sobre el grupo completo y dentro de una transacción de BD.
   - Opción rápida si no se quiere migrar aún: marcar los asientos generados como no editables/no eliminables desde la UI y ofrecer “eliminar operación completa”.

3. **Validación de entrada (C3, H3, H4, H7).**
   - `amount`: `required|numeric|decimal:0,2|gt:0` y máximo según columna.
   - `account_id`: `required|exists:accounts,id` y validar flags (`allows_purchases`, `allows_withdrawal`, `allows_deposits`, `allows_transfers`).
   - `category_id`: `required|exists:categories,id` y bloquear categorías de sistema donde corresponda.
   - `effective_date`: `required|date`.
   - `payment_day`: `required|integer|between:1,31`.
   - Transferencia: `from_account_id != to_account_id` y fondos suficientes (o política de sobregiro explícita).
   - Evitar `find()` sin comprobar `null` antes de usar el modelo.

4. **Atomicidad y concurrencia (C4).**
   - Envolver cada operación en `DB::transaction()`.
   - Bloquear las cuentas implicadas (`lockForUpdate`) antes de calcular saldos.
   - Considerar `increment/decrement` atómico o recalcular el saldo desde transacciones.
   - Evitar doble envío con `wire:loading.attr="disabled"` y `wire:target`.

5. **Suscripciones borradas (C6).**
   - Agregar `whereNull('s.deleted_at')` en `Subscription::getAll()`.
   - Agregar prueba de regresión.

6. **Dashboard (C7).**
   - Quitar los `wire:click="order(...)"` o implementar `order()`, `$sort` y `$direction` en `Dashboard`.
   - Agregar prueba de que el componente renderiza y responde sin error.

7. **Migración (C8).**
   - Corregir `suscriptions` → `subscriptions` en `down()`.

8. **Acceso (C5, H12).**
   - Deshabilitar el registro público en `config/fortify.php` o implementar una lista de correos permitidos mientras la app sea global.
   - Generar `APP_KEY` por entorno; quitar `APP_DEBUG=true` del ejemplo o dejarlo documentado solo para local.
   - Revisar si se quiere agregar un rol `admin` con Spatie para las operaciones destructivas.

**Criterios de aceptación Fase 0**
- Suite verde y nuevas pruebas para cada bug.
- Editar un `Abono` en la misma cuenta y en otra cuenta deja saldos correctos.
- Editar/eliminar una transferencia no deja asientos huérfanos.
- Montos negativos, cero, no numéricos, IDs inexistentes y transferencias a la misma cuenta son rechazados con mensajes de validación.
- `Subscription::getAll()` no muestra borrados.
- El dashboard no lanza error al hacer clic en cabeceras.
- `migrate:rollback` de suscripciones funciona.

### Fase 1 — Modelo contable y catálogos (3-5 días)

**Objetivo:** dejar de depender de IDs mágicos y permitir administrar la base financiera.

1. **Modelo de asientos.**
   - Introducir `transaction_group_id`/`operation_id` y, si aplica, `operation_type` (`purchase`, `withdrawal`, `deposit`, `savings`, `transfer`, `subscription`).
   - Documentar que un grupo puede tener uno o dos asientos.
   - Migrar los datos existentes agrupando por cercanía/descripción solo si es seguro; para datos nuevos, generar el grupo desde el inicio.

2. **Saldo inicial y reconciliación.**
   - Agregar una transacción de apertura por cuenta o un campo `opening_balance` explícito.
   - Crear un comando `finance:reconcile` que compare `available_amount` con la suma de transacciones y reporte diferencias.
   - Agregar prueba que valide la reconciliación después de cada operación.

3. **CRUD de cuentas y categorías.**
   - Nuevas páginas/componentes Livewire (por ejemplo `Accounts` y `Categories`).
   - Crear, editar, archivar/restaurar, validar nombre único y flags.
   - No permitir borrar una cuenta/categoría con transacciones activas; ofrecer archivar.
   - Al crear una cuenta, permitir saldo inicial y generar el asiento de apertura.

4. **Eliminar IDs hardcodeados.**
   - Sustituir `Account::find(1)` y `Account::find(3)` por una configuración explícita: flags (`is_cash`, `is_savings`) o tabla `settings` con `cash_account_id`/`savings_account_id`.
   - Migrar los seeders para asignar esos flags.

5. **Seeders idempotentes.**
   - Usar `firstOrCreate`/`upsert` con claves naturales (`name`).
   - Hacer que `DatabaseSeeder` llame a `AccountSeeder` y `CategorySeeder`.
   - Documentar que los IDs siguen siendo estables solo si la base se siembra desde cero.

6. **Modelo Eloquent.**
   - Agregar relaciones `account()`, `category()`, `transactions()`, `subscriptions()`.
   - Agregar casts: `amount` decimal:2, `available_amount` decimal:2, `effective_date` date, `payment_day` integer, booleanos.
   - Considerar enums PHP para `type` y `operation_type`.

7. **Base de datos.**
   - Unificar precisión monetaria (por ejemplo `decimal(12,2)` o integer cents).
   - Agregar índices en `account_id`, `category_id`, `effective_date`, `deleted_at`, `subscription_id`.
   - Agregar constraints/validaciones de `payment_day` y unicidad de nombres cuando aplique.

**Criterios de aceptación Fase 1**
- Se puede crear una cuenta con saldo inicial y una categoría desde la UI.
- Las operaciones no dependen de IDs 1/2/3.
- `finance:reconcile` no reporta diferencias después de los flujos principales.
- `migrate --seed` deja la app usable en una base limpia.

### Fase 2 — Suscripciones completas (2-3 días)

1. Agregar a `subscriptions`: `is_active`, `last_paid_at`, `next_payment_date` y, si se decide, `payment_transaction_id`.
2. Al pagar una suscripción:
   - Crear la transacción y vincularla a la suscripción.
   - Registrar el periodo pagado.
   - Evitar duplicados para el mismo periodo.
   - Actualizar `last_paid_at`/`next_payment_date`.
3. Mostrar en la tabla: próximo vencimiento, estado (al día/vencida/pausada) y último pago.
4. Agregar un comando programado que **avise** de vencimientos (no que cree cargos automáticamente sin confirmación).
5. Agregar filtro de suscripciones activas/inactivas y acción de pausar/reanudar.

**Criterios de aceptación**
- No se puede pagar dos veces la misma suscripción en el mismo periodo.
- La tabla muestra el próximo vencimiento correcto.
- Un pago genera una transacción vinculada y actualiza los campos de control.

### Fase 3 — Reportes, UX y localización (3-5 días)

1. **Filtros y totales.**
   - Rango de fechas, cuenta, categoría y tipo.
   - Totales de ingresos, egresos y neto.
   - Resumen mensual en el dashboard.
2. **Dashboard.**
   - Total por cuenta y total general.
   - Gráfico simple de ingresos/egresos por mes (opcional).
   - Paginación o enlace “ver todas”.
3. **Exportación.**
   - CSV de transacciones filtradas; PDF opcional.
4. **UX Livewire.**
   - `wire:key` en filas.
   - `wire:loading`/botones deshabilitados.
   - Mensajes de confirmación claros y cierre correcto de modales.
   - Inputs `type="number"`/`inputmode="decimal"` y `step="0.01"`.
5. **Localización.**
   - `locale = 'es'`, `timezone` configurable, carpeta `lang/es`.
   - Moneda configurable (`MXN` por defecto) y formato con `NumberFormatter`.
   - Traducir textos de Jetstream que se muestren.
6. **Dependencias frontend.**
   - Actualizar Vite/Tailwind/`axios` y resolver los 18 avisos de `npm audit` (empezar por el crítico de `form-data` y los altos de `axios`/Vite).
   - Mover Flowbite/Pikaday/SweetAlert a npm o agregar SRI.
   - Evaluar si Flowbite sigue siendo necesario.
7. **Limpieza.**
   - Eliminar vistas/código de teams, API y welcome que no se usan.
   - Corregir README y `.env.example`.

**Criterios de aceptación**
- Los listados se pueden filtrar y muestran totales correctos.
- La UI no permite doble envío.
- Validaciones y fechas se muestran en español.
- No quedan recursos CDN sin SRI.

### Fase 4 — Preparación multi-usuario (solo cuando se solicite)

No implementar ahora. Cuando toque:

1. Agregar `user_id` a `accounts`, `categories`, `transactions` y `subscriptions`.
2. Migrar los datos globales al usuario administrador inicial.
3. Agregar global scopes/policies para que cada usuario solo vea lo suyo.
4. Usar Spatie Permission para roles (`admin`, `user`) y permisos destructivos.
5. Agregar invitaciones y límites de registro.
6. Agregar auditoría (`created_by`, `updated_by`, `deleted_by`).
7. Pruebas de aislamiento entre usuarios.

> Mientras esta fase no se haga, no agregar `user_id` “por si acaso”: complicaría las consultas y no aportaría valor al alcance global actual.

### Fase 5 — Calidad continua

1. GitHub Actions: `composer install`, `php artisan test`, `pint --test`, `npm run build`.
2. Scripts en `composer.json`: `composer test` y `composer lint`.
3. Ejecutar Pint sobre todo el proyecto en un commit separado (hoy fallan 24 archivos).
4. Actualizar README con el estado real (pruebas, funciones activas, requisitos).
5. Mantener `AGENTS.md` sincronizado con los cambios de arquitectura.

## 5. Decisiones que conviene confirmar antes de implementar

1. **Sobregiros:** ¿bloquear cualquier `Cargo` que deje saldo negativo o permitirlo con advertencia? Recomendación: bloquear por defecto.
2. **Operaciones de dos asientos:** ¿editar/eliminar el grupo completo o bloquear la edición individual? Recomendación: grupo completo.
3. **Cuentas/categorías:** ¿CRUD completo ahora o mantener solo seeds hasta el multi-usuario? Recomendación: CRUD ahora, porque sin ello la app no es usable más allá de los datos de ejemplo.
4. **Registro:** ¿deshabilitarlo, usar allowlist o dejar abierto? Recomendación: deshabilitarlo/allowlist mientras los datos sean globales.
5. **Suscripciones:** ¿solo recordatorios o generación automática de transacciones? Recomendación: recordatorios primero, generación opt-in después.
6. **Moneda/timezone:** ¿MXN y `America/Mexico_City`? Recomendación: configurables por `.env`, con esos valores por defecto.
7. **Multi-usuario:** ¿se hará con `user_id` en cada tabla o con “workspaces/equipos”? Recomendación: `user_id`/`workspace_id` cuando se aborde, no antes.

## 6. Orden recomendado de implementación

| Orden | Fase | Entregable | Riesgo si no se hace |
|---|---|---|---|
| 1 | Fase 0 | Saldos y asientos protegidos, validaciones, registro cerrado | Datos corruptos y acceso indebido |
| 2 | Fase 1 | CRUD de cuentas/categorías, modelo contable, reconciliación | App limitada a datos de ejemplo |
| 3 | Fase 2 | Suscripciones con historial y vencimientos | Pagos duplicados y falta de seguimiento |
| 4 | Fase 3 | Reportes, filtros, localización y UX | Poca utilidad para el día a día |
| 5 | Fase 4 | Multi-usuario | Solo cuando se solicite |
| 6 | Fase 5 | CI y calidad continua | Regresiones silenciosas |

## 7. Quick wins que se pueden hacer primero

Si se quiere empezar con cambios pequeños y de alto impacto:

1. Corregir `Transactions::update()` para respetar el tipo.
2. Agregar validaciones `numeric|gt:0|decimal:0,2` y `exists` a todos los formularios.
3. Envolver las operaciones multi-asiento en `DB::transaction()`.
4. Agregar `whereNull('s.deleted_at')` en `Subscription::getAll()`.
5. Corregir el typo de la migración de suscripciones.
6. Quitar los `wire:click="order(...)"` del dashboard.
7. Deshabilitar el registro público.
8. Quitar la `APP_KEY` y `APP_DEBUG=true` de `.env.example`.
9. Agregar pruebas de regresión para los casos anteriores.
10. Ejecutar Pint solo sobre los archivos tocados.
