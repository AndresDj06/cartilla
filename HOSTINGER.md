# Guía de Instalación en Hostinger (100% Sin SSH)

Esta guía está diseñada para instalar **Cartilla** en **Hostinger** (o cualquier hosting compartido con hPanel/cPanel) utilizando únicamente la interfaz web, **sin necesidad de abrir consola ni configurar llaves SSH**.

---

## Requisitos en Hostinger
- Un plan de hosting en Hostinger (Single, Premium, Business o Cloud).
- Versión de PHP configurada en **8.1** u **8.2** (en hPanel → *Avanzado* → *Configuración de PHP*).

---

## Paso a paso (Tiempo estimado: 3 a 5 minutos)

### 1. Descargar el proyecto
1. En GitHub, haz clic en el botón verde **Code** → **Download ZIP**.
2. Guarda el archivo `.zip` en tu computadora.

---

### 2. Subir y extraer los archivos en Hostinger
1. Entra a tu **hPanel** de Hostinger.
2. Ve a **Sitios web** → Administrar → **Administrador de archivos** (File Manager).
3. Entra en la carpeta **`public_html/`**.
4. Sube el archivo `.zip` que descargaste.
5. Haz clic derecho sobre el archivo subido y selecciona **Extraer**.
6. **Importante sobre la ubicación de los archivos:**
   Asegúrate de que los archivos (`index.php`, `.htaccess`, `.env.example`, carpetas `app/`, `public/`, `database/`) queden ubicados **directamente dentro de `public_html/`**, y no dentro de una subcarpeta repetida.

---

### 3. Crear la Base de Datos MySQL
1. En hPanel, ve al menú izquierdo a **Bases de datos** → **Bases de datos MySQL**.
2. Completa los campos:
   - **Nombre de la base de datos**: ej. `cartilla` *(Hostinger le agregará un prefijo como `u123456789_cartilla`)*.
   - **Nombre de usuario**: ej. `admin` *(quedará como `u123456789_admin`)*.
   - **Contraseña**: Escribe una contraseña segura y guárdala.
3. Haz clic en **Crear**.

---

### 4. Importar los datos con phpMyAdmin (Sin terminal)
1. En la lista de bases de datos de Hostinger, busca la base que acabas de crear y haz clic en el botón **Ingresar a phpMyAdmin**.
2. En phpMyAdmin, asegúrate de estar dentro de tu base de datos y haz clic en la pestaña superior **Importar**.
3. Haz clic en **Seleccionar archivo** y busca dentro de los archivos extraídos:
   `database/cartilla.sql`
4. Baja hasta el final de la página y haz clic en **Continuar** (o **Importar**).
5. Espera unos segundos. phpMyAdmin confirmará que se importaron correctamente las **12 tablas**, el catálogo de **160 ejercicios**, las cuentas de prueba y el superadministrador.

---

### 5. Configurar el archivo `.env`
1. Vuelve al **Administrador de archivos** en `public_html/`.
2. Localiza el archivo `.env.example`.
3. Haz una copia o renómbralo a **`.env`** (con el punto al inicio).
4. Ábrelo para editar y coloca los datos reales de tu base de datos de Hostinger:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=u336988965_cartilla
DB_USER=u336988965_usuario
DB_PASS=tu_clave_creada_en_el_paso_3

APP_TZ=America/Bogota
APP_DEBUG=0
```
> ⚠️ **Atención:** En `DB_HOST` pon únicamente `localhost` o `127.0.0.1`. **Nunca escribas `https://` ni la URL de tu página web**, de lo contrario MySQL intentará conectarse a internet en lugar de al servidor local de bases de datos.

5. Guarda los cambios.

---

## Solución a errores comunes en terminal (SSH)

### Error: `getaddrinfo for https failed: Name or service not known`
- **Causa:** En el archivo `.env` se colocó `https://...` en el campo `DB_HOST`.
- **Solución:** Abre el archivo `.env` y cámbialo a:
  ```ini
  DB_HOST=localhost
  ```
  *(El sistema ahora también limpia automáticamente prefijos http/https por si acaso, pero el valor correcto es `localhost`).*

---

### 6. ¡Listo! Abre tu dominio
Visita tu dominio en el navegador (ejemplo: `https://tudominio.com`). La portada de **Cartilla** cargará de inmediato.

---

## Cuentas de demostración incluidas
Al haber importado `cartilla.sql`, ya cuentas con accesos de prueba listos para usar en la pantalla de inicio de sesión:

| Rol | Usuario / Correo | Contraseña |
| --- | --- | --- |
| **Docente demo** | `profe.demo` / `docente@cartilla.test` | `Demo2026!` |
| **Estudiante demo** | `estudiante.demo` / `estudiante@cartilla.test` | `Demo2026!` |
| **Super administrador** | `jrodriguez` / `jhonatanlaur@gmail.com` | `Demo2026!` |

*(Código de la clase de demostración del ciclo del agua: `AGUA26`)*
