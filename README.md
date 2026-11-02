# FinanSmart

> Sistema web de gestión financiera personal diseñado para el control integral y eficiente de cuentas bancarias, transacciones, transferencias y seguimiento de suscripciones recurrentes.

Construido sobre el ecosistema **TALL Stack** (*Tailwind CSS, Alpine.js, Laravel, Livewire*), ofreciendo una experiencia reactiva moderna y fluida sin necesidad de separar el backend del frontend en aplicaciones independientes.

---

##  Características Principales

* **Tablero Principal (Dashboard):**
  * Resumen financiero con saldos de cuentas en tiempo real (efectivo, nómina, ahorros).
  * Listado de las transacciones más recientes realizadas.

* **Gestión de Transacciones:**
  * Registro de transacciones con clasificación por tipo, categoría, cuenta y fecha efectiva.
  * Soporte para diferentes tipos de operaciones:
    * **Depósitos** (ingresos)
    * **Retiros** (gastos)
    * **Traspasos / Transferencias** entre cuentas
    * **Ahorros y Suscripciones**
  * Búsqueda en tiempo real, ordenamiento dinámico por columnas y paginación reactiva con Livewire.
  * Borrado lógico (*Soft Deletes*) y confirmación mediante diálogos interactivos (SweetAlert2).

* **Control de Suscripciones Periódicas:**
  * Administración centralizada de pagos recurrentes (servicios, plataformas de streaming, gimnasio, colegiaturas, etc.).
  * Configuración de día de corte/pago mensual, monto y cuenta de débito asociada.

* **Cuentas y Categorización Flexible:**
  * Configuración de permisos por cuenta (si permite retiros, depósitos o transferencias).
  * Catálogo de categorías para clasificar gastos e ingresos (Hogar, Salud, Transporte, Servicios, etc.).

* **Seguridad y Control de Acceso:**
  * Autenticación robusta provista por **Laravel Jetstream** y **Fortify**.
  * Soporte para autenticación de dos factores (**2FA**).
  * Gestión de sesiones activas en otros navegadores y dispositivos.
  * Control de roles y permisos mediante **Spatie Laravel-Permission**.
  * API tokens protegidos con **Laravel Sanctum**.

---

## Stack Tecnológico

| Capa | Tecnologías |
| :--- | :--- |
| **Backend** | [PHP 8.2+](https://www.php.net/), [Laravel 12](https://laravel.com/), [Livewire 3](https://laravel-livewire.com/) |
| **Frontend** | [Blade](https://laravel.com/docs/blade), [Tailwind CSS 3](https://tailwindcss.com/), [Alpine.js 3](https://alpinejs.dev/) |
| **Empaquetador** | [Vite 4](https://vitejs.dev/) |
| **Seguridad** | [Laravel Jetstream](https://jetstream.laravel.com/), [Laravel Sanctum](https://laravel.com/docs/sanctum), [Spatie Permission](https://spatie.be/docs/laravel-permission) |
| **Alertas & UI** | [Livewire Alert](https://github.com/jantinnerezo/livewire-alert), [SweetAlert2](https://sweetalert2.github.io/) |
| **Base de Datos** | MySQL / MariaDB con Eloquent ORM |

---

## Requisitos Previos

Antes de instalar el proyecto, asegúrate de tener instalado en tu entorno de desarrollo:

* **PHP** >= 8.2 (con extensiones `pdo_mysql`, `mbstring`, `openssl`, `curl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
* **Composer** >= 2.x
* **Node.js** >= 18.x y **NPM**
* Servidor de base de datos **MySQL** o **MariaDB** (o un entorno local como [Laragon](https://laragon.org/) / XAMPP)

---

## Instalación y Configuración Local

Sigue estos pasos para clonar y ejecutar el proyecto en tu entorno local:

### 1. Clonar el repositorio
```bash
git clone git@github.com:octavioln/FinanSmart.git
cd FinanSmart
```

### 2. Instalar dependencias del Backend (Composer)
```bash
composer install
```

### 3. Instalar dependencias del Frontend (NPM)
```bash
npm install
```

### 4. Configurar el archivo de entorno
Copia la plantilla `.env.example` para generar tu archivo `.env`:
```bash
cp .env.example .env
```

Configura tus credenciales de base de datos en el archivo `.env`:
```env
APP_NAME=FinanSmart
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finansmart
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Generar la clave de la aplicación
```bash
php artisan key:generate
```

### 6. Ejecutar migraciones y sembrar datos iniciales
Ejecuta las migraciones de base de datos y carga los catálogos base (cuentas y categorías iniciales):
```bash
php artisan migrate
php artisan db:seed --class=AccountSeeder
php artisan db:seed --class=CategorySeeder
```

*(Opcional: Si configuras `DatabaseSeeder`, puedes ejecutar directamente `php artisan migrate --seed`).*

### 7. Compilar activos con Vite
* **Para desarrollo con recarga rápida (HMR):**
  ```bash
  npm run dev
  ```
* **Para generar los archivos listos para producción:**
  ```bash
  npm run build
  ```

### 8. Iniciar el servidor local
Si no estás usando un host virtual de Laragon (`http://finansmart.test`), inicia el servidor embebido de PHP:
```bash
php artisan serve
```

Abre tu navegador en [http://localhost:8000](http://localhost:8000).

---

## Estructura del Proyecto

```
FinanSmart/
├── app/
│   ├── Actions/            # Lógica desacoplada de usuarios y autenticación (Fortify / Jetstream)
│   ├── Http/
│   │   ├── Livewire/       # Componentes reactivos centrales (Dashboard, Subscriptions, Transactions)
│   │   └── Middleware/     # Middlewares HTTP
│   └── Models/             # Modelos Eloquent (Account, Transaction, Subscription, Category, User)
├── database/
│   ├── migrations/         # Esquemas de base de datos
│   └── seeders/            # Datos precargados (cuentas de prueba y categorías de gastos)
├── public/                 # Directorio raíz público (index.php y assets compilados)
├── resources/
│   ├── css/                # Estilos base con Tailwind CSS
│   ├── js/                 # Configuración de Alpine.js y alertas SweetAlert2
│   └── views/              # Vistas Blade y componentes de Livewire
├── routes/
│   ├── web.php             # Rutas web de la aplicación
│   └── api.php             # Rutas API con Sanctum
└── tests/
    ├── Feature/            # Pruebas funcionales de autenticación y componentes Livewire
    └── Unit/               # Pruebas unitarias de modelos, relaciones y cálculo de saldos
```

---

## Pruebas Automatizadas

El proyecto cuenta con una suite integral de **67 pruebas automatizadas (148 aserciones)** desarrolladas con **PHPUnit** y los helpers de pruebas de **Livewire**, configuradas para ejecutarse sobre una base de datos **SQLite en memoria (`:memory:`)** para máxima velocidad e independencia del entorno.

### Cobertura de la Suite

* **Pruebas Unitarias (`tests/Unit/`):**
  * **`AccountTest`**: Validación de atributos, actualización dinámica de saldos (`updateAvailableAmount` para `Cargo` y `Abono`) y soporte de *Soft Deletes*.
  * **`CategoryTest`**: Creación de categorías y borrado lógico.
  * **`TransactionTest`**: Relaciones con cuentas y categorías, consultas optimizadas con paginación (`getAll`), búsqueda reactiva, consulta de recientes para dashboard (`getMostRecent`) y exclusión de registros eliminados.
  * **`SubscriptionTest`**: Creación, relaciones, búsquedas paginadas y borrado lógico.

* **Pruebas Funcionales / Livewire (`tests/Feature/`):**
  * **`DashboardTest`**: Protección de rutas para usuarios invitados y renderizado del tablero con saldos de cuentas y movimientos recientes.
  * **`SubscriptionsLivewireTest`**: Control de acceso, validación de campos obligatorios, alta de suscripciones recurrentes, carga y edición en modal, eliminación suave y ordenamiento de columnas.
  * **`TransactionsLivewireTest`**: Validación completa de la lógica contable y operativa:
    * Registro de gasto estándar (`Cargo`) con decremento automático del saldo de la cuenta.
    * Edición y recálculo de balance (por diferencia de montos en la misma cuenta o traspaso entre cuentas distintas).
    * Retiro de cajero (`saveWithdraw`): doble asiento contable (cargo en cuenta bancaria y abono en efectivo) y ajuste simultáneo de saldos.
    * Depósito (`saveDeposit`): abono e incremento del balance.
    * Traspaso a ahorro (`saveSavings`) y transferencias entre cuentas (`saveTransfer`).
    * Pago programado de suscripciones (`saveSubscription`).
    * Reversión de saldo al eliminar una transacción (`destroy`).
  * **Autenticación y Seguridad (Jetstream):** Pruebas de registro, login, 2FA, reseteo de contraseñas y cierre de sesiones remotas.

### Comandos de Ejecución

* **Ejecutar toda la suite:**
  ```bash
  php artisan test
  ```

* **Ejecutar solo pruebas unitarias:**
  ```bash
  php artisan test --testsuite=Unit
  ```

* **Ejecutar solo pruebas funcionales:**
  ```bash
  php artisan test --testsuite=Feature
  ```

* **Ejecutar pruebas de un componente o módulo específico:**
  ```bash
  php artisan test --filter=TransactionsLivewireTest
  php artisan test --filter=SubscriptionsLivewireTest
  php artisan test --filter=DashboardTest
  php artisan test --filter=AccountTest
  ```

---

## Licencia

Este proyecto se distribuye bajo la licencia **MIT**. Para más detalles, consulta el archivo [LICENSE](LICENSE) o la documentación oficial de la licencia.
