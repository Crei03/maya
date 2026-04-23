## Inicializacion del Proyecto

### 1. Requisitos Previos

Antes de ejecutar el proyecto, asegurate de tener instalado:

- **Node.js** (version 18 o superior)
- **PHP** (version 8.1 o superior)
- **Laravel** (incluido en las dependencias del proyecto)
- **Composer** (para gestionar dependencias PHP)
- **MySQL** (para la base de datos)

### 2. Base de Datos - Migraciones y Seeders

Para configurar la base de datos "Maya", sigue estos pasos:

1. Crea la base de datos "Maya" en MySQL:
   ```sql
   CREATE DATABASE maya;
   ```

2. Configura las credenciales de la base de datos en el archivo `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=maya
   DB_USERNAME=tu_usuario
   DB_PASSWORD=tu_password
   ```

3. Instala las dependencias de PHP:
   ```bash
   composer install
   ```

4. Ejecuta las migraciones para crear las tablas:
   ```bash
   php artisan migrate
   ```

5. (Opcional) Ejecuta los seeders para poblar la base de datos con datos de prueba:
   ```bash
   php artisan db:seed
   ```

### 3. Ejecutar el Proyecto

1. Instala las dependencias de Node.js:
   ```bash
   npm install
   ```

2. Inicia el servidor de desarrollo y observa los cambios en tiempo real:
   ```bash
   npm run watch
   ```

3. Abre tu navegador y visita: `http://localhost:8000`

El comando `npm run watch` detectara cambios en los archivos y recargara la pagina automaticamente.
