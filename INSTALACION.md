# Instalar Cartilla en un servidor

Este paquete trae el sistema completo y la base de datos lista para importar. No necesita Composer ni npm.

## Requisitos

- PHP 8.0 o superior con las extensiones `pdo_mysql` y `mbstring`.
- MySQL 5.7+ o MariaDB 10.3+ (con soporte de `utf8mb4` y columnas `JSON`).
- Apache con `mod_rewrite` y `AllowOverride All` (lo normal en hosting compartido con cPanel).
- HTTPS recomendado: las cookies se marcan como seguras automáticamente cuando el sitio va por HTTPS.

## Contenido del paquete

```
app/                 Código MVC (controladores, modelos, servicios, vistas)
config/app.php       Configuración no secreta (identidad del super administrador, cuentas demo)
bin/cartilla         Consola (instalar, superadmin, estado)
public/              Raíz web: index.php, CSS, JS e imágenes
database/cartilla.sql  Base lista para importar: catálogo, datos demo y super administrador
database/schema.sql    Solo la estructura (12 tablas)
database/contenido.php Catálogo en PHP (lo usa «php bin/cartilla instalar»)
.htaccess            Envía todo a public/ y bloquea app/, config/, database/, bin/ y .env
.env.example         Plantilla de configuración
```

## Paso a paso (hosting compartido / cPanel)

1. **Sube y descomprime** el zip. Puedes ponerlo en la raíz del dominio (`public_html/`) o en una subcarpeta (`public_html/cartilla/`); el sistema detecta la ruta solo.
2. **Crea la base de datos** y un usuario con todos los permisos sobre ella (cPanel → Bases de datos MySQL).
3. **Importa** `database/cartilla.sql` en esa base (phpMyAdmin → la base → Importar). Debe quedar con 12 tablas.
4. **Crea el archivo `.env`** a partir de `.env.example` (en el Administrador de archivos: copiar y renombrar) y completa:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=nombre_de_tu_base
   DB_USER=usuario_de_la_base
   DB_PASS=clave_de_la_base
   APP_TZ=America/Bogota
   APP_DEBUG=0
   SUPERADMIN_CLAVE=
   ```
   `SUPERADMIN_CLAVE` solo se usa con la consola; puedes dejarla vacía si no tienes SSH.
5. **Abre el sitio**. Si ves la portada, está funcionando. Si sale «Sin conexión con la base de datos», revisa los datos del `.env`.
6. Comprueba que `https://tu-dominio/.env` responda **403 Prohibido**.

### Si tu hosting deja elegir la raíz web

Lo más seguro es apuntar el dominio directamente a la carpeta `public/`. Así nada fuera de ella queda expuesto aunque el `.htaccess` de la raíz no se aplique.

## Con acceso SSH (VPS o hosting con terminal)

En lugar de importar el SQL puedes crear todo con la consola, lo que además deja las fechas de los datos demo al día de hoy (rachas y «últimos 7 días» se ven completas):

```bash
cp .env.example .env        # y completa los datos
php bin/cartilla instalar   # crea tablas, catálogo, demo y super administrador (borra lo que haya)
php bin/cartilla estado     # cuenta registros por tabla
```

Para no cargar datos demo: `php bin/cartilla instalar --sin-demo`.

## Cuentas incluidas en `cartilla.sql`

| Rol | Usuario | Correo | Clave |
| --- | --- | --- | --- |
| Super administrador | `jrodriguez` | jhonatanlaur@gmail.com | La misma que usaste en el entorno local (`SUPERADMIN_CLAVE`) |
| Docente demo | `profe.demo` | docente@cartilla.test | `Demo2026!` |
| Estudiante demo | `estudiante.demo` | estudiante@cartilla.test | `Demo2026!` |

- Clase de demostración: código `AGUA26`, con 6 estudiantes ficticios y su historial.
- Para cambiar la clave del super administrador con SSH: pon la nueva en `SUPERADMIN_CLAVE` y ejecuta `php bin/cartilla superadmin --restablecer`.
- Las cuentas demo son públicas (sus claves están en la pantalla de ingreso). Si el sitio será usado por personas reales después de la hackatón, bórralas o cambia sus claves.

## Notas

- Los datos demo de `cartilla.sql` tienen fechas del 26 de octubre de 2026: con el paso de los días las rachas demo se reinician. Reinstalar con la consola las pone al día.
- `APP_DEBUG=1` muestra el detalle técnico de los errores; déjalo en `0` en producción.
- Copias de seguridad: basta con exportar la base y guardar el `.env`.
