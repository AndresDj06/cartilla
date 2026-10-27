# Cartilla

**Lo visto en clase, practicado cada día.**

Cartilla une lo mejor de dos plataformas que exploramos para la hackatón:

| De Educaplay | De Duolingo | En Cartilla |
| --- | --- | --- |
| El docente crea actividades de varios tipos (test, completar, ordenar, relacionar…) | Lecciones cortas en una ruta que se abre en orden | El docente pega el texto de su tema y obtiene **dos lecciones de juegos** que se organizan como **ruta diaria** |
| Game Pin: se entra a una actividad con un código | Racha, XP, meta diaria y vidas | La clase se comparte con un **código de 6 caracteres**; cada estudiante tiene racha, XP, meta y vidas |
| La actividad se juega una vez | La app adapta y repite lo que fallas | Lo que se falla entra a un **repaso espaciado** (vuelve en 1, 2, 4, 7 y 15 días) |
| Resultados por actividad | — | **Panel docente**: acierto por pregunta (de la más fallada a la menos), respuestas equivocadas más comunes, avance por estudiante y CSV |

- **Para quién:** docentes de colegios y universidades de Colombia y sus estudiantes.
- **Problema:** las actividades de clase se juegan una vez y se olvidan; el docente no sabe qué parte del tema no se entendió.
- **Resultado:** en menos de tres minutos un estudiante hace su primera lección y ve su resultado; en cinco minutos un docente tiene juegos revisados y un código para compartir.

---

## Instalación (XAMPP en macOS, Windows o Linux)

Requisitos: PHP 8.0 o superior con PDO MySQL, MySQL/MariaDB y Apache con `mod_rewrite` (vienen con XAMPP). No hay dependencias de Composer ni de npm.

1. Copia la carpeta del proyecto a `htdocs/educa` y enciende Apache y MySQL.
2. Crea la configuración local:
   ```bash
   cp .env.example .env
   ```
   Ajusta usuario y clave de MySQL si no son `root` sin clave, y define `SUPERADMIN_CLAVE` (mínimo 8 caracteres).
3. Crea la base, el catálogo y los datos de demostración:
   ```bash
   php bin/cartilla instalar
   ```
4. Abre <http://localhost/educa/>.

Sin Apache también funciona con el servidor embebido de PHP:

```bash
php -S localhost:8080 -t public public/index.php
```

Otros comandos:

```bash
php bin/cartilla superadmin               # crea o verifica el super administrador (no toca la clave si ya existe)
php bin/cartilla superadmin --restablecer # vuelve a poner la clave de SUPERADMIN_CLAVE
php bin/cartilla estado                   # cuenta registros por tabla
```

> `php bin/cartilla instalar` **borra y recrea** la base. Úsalo para dejar los datos de demostración limpios antes de presentar.

## Cuentas de demostración

Valores de prueba, también visibles en la pantalla de ingreso:

| Rol | Usuario | Correo | Clave |
| --- | --- | --- | --- |
| Docente | `profe.demo` | docente@cartilla.test | `Demo2026!` |
| Estudiante | `estudiante.demo` | estudiante@cartilla.test | `Demo2026!` |

- Clase de demostración: código **`AGUA26`** («El ciclo del agua en el Chocó»), con 6 estudiantes ficticios e historial de respuestas para que el panel docente tenga datos.
- Se ingresa con el correo **o** con el usuario, en el mismo campo.
- El super administrador (JHONATAN CORDOBA · `jrodriguez` · jhonatanlaur@gmail.com) se crea con `php bin/cartilla superadmin`; su clave vive solo en `.env`.

## Recorrido para el jurado (5 minutos)

1. **Portada** → al hacer scroll la ola sube y hunde el titular «Que no se te olvide» (lo que no se repasa se olvida). Responde la tarjeta rosada (pregunta real, la corrige el servidor) y mira cómo la tarjeta de repaso cambia; usa el interruptor «Sin repaso / Con repaso» para ver la curva del olvido.
2. **Empezar sin registrarme** → seis preguntas cortas. A la izquierda, la «etiqueta del cuaderno» se llena con cada respuesta. Se puede volver atrás o saltar a cualquier paso respondido.
3. **Resumen** → cada decisión dice qué cambió (por ejemplo «10 minutos → 8 ejercicios por lección y meta de 20 XP») con un enlace «Cambiar».
4. **Primera lección** → seis tipos de juego, corrección inmediata con explicación, vidas, lo fallado vuelve al final. Todo funciona con teclado (1–4 para elegir, Enter para comprobar/continuar).
5. **Resultado** → XP, acierto, racha, meta del día y lista de errores. Propone crear cuenta explicando qué datos pide y para qué; el avance de invitado pasa entero a la cuenta.
6. **Docente**: «Soy docente» → pega un texto (o «Usar un texto de ejemplo») → revisa y corrige cada ejercicio (cada uno muestra la oración de la que salió) → «Guardar y publicar» → crea cuenta → la clase queda publicada con código.
7. **Panel docente** con `profe.demo` → clase `AGUA26`: acierto por pregunta, respuestas equivocadas frecuentes, estudiantes y CSV.

## Cómo cambian las respuestas del onboarding la experiencia

| Respuesta | Efecto concreto |
| --- | --- |
| Rol | Estudiante → cursos para practicar. Docente → creador de juegos desde su texto. |
| Objetivo | Sugiere minutos al día (evaluación/examen 15, reforzar/entre clases 10, nuevo/diagnóstico 5). Con evaluación o examen, el repaso aparece primero en la ruta. |
| Tema | Curso del catálogo o texto propio del docente. |
| Nivel (estudiante) | Desde cero: unidad 1, banco de palabras, 3 vidas. Básico: empieza en la unidad 2. Reto: unidad 2, sin banco, 2 vidas. |
| Grado (docente) | Primaria/secundaria con banco de palabras; media y universidad sin banco; universidad con 2 vidas. Aplica a todos los estudiantes de la clase. |
| Minutos | 5 → 5 ejercicios y meta 10 XP; 10 → 8 y 20 XP; 15 → 10 y 30 XP. |
| Juegos | Cada lección escoge primero esos tipos y completa con los demás si faltan. |

## Arquitectura (PHP MVC + MySQL)

```
public/index.php          Controlador frontal y rutas
app/Core/                 Router, Controller, Model (PDO), View, Session, Auth, Env
app/Controllers/          Inicio, Onboarding, Ruta, Leccion, Api, Auth, Clase, Cuenta, Docente, Admin
app/Models/               Usuario, Perfil, Curso, Ejercicio, Intento, Progreso
app/Services/             Practica (ruta, intentos, XP, racha), Evaluador (corrección), Personalizador,
                          GeneradorJuegos (texto → ejercicios), Instalador, SuperAdministradorService
app/Views/                layouts (sitio, app, enfoque) y vistas por módulo
database/schema.sql       12 tablas InnoDB utf8mb4 con claves foráneas
database/contenido.php    Catálogo: 4 cursos, 16 lecciones, 160 ejercicios
bin/cartilla              Consola: instalar, superadmin, estado
```

Decisiones clave:

- **Invitado real en la base.** Quien llega sin cuenta recibe una fila `usuarios` con rol `invitado` y una cookie de 90 días. Al registrarse se completa esa misma fila, así XP, racha, repasos y clases no se pierden.
- **Corrección en el servidor.** El navegador nunca recibe las soluciones; cada respuesta se guarda al momento (`/api/intentos/{id}/responder`). Si se recarga o se corta la conexión, la lección continúa donde quedó.
- **Generador explicable y local.** `GeneradorJuegos` usa reglas sobre el texto (palabras clave por frecuencia y mayúsculas, oraciones cortas, definiciones «X es…», distractores de la misma clase de palabra). No llama a servicios externos.
- **Repaso espaciado** con cajas de Leitner (`repasos.caja`, `repasos.proxima`).

Seguridad: consultas preparadas, `password_hash`, token CSRF en todo POST y en la API, sesiones `httponly` y `SameSite=Lax` con firma por usuario, freno de 30 s tras 5 ingresos fallidos, permisos por rol (el super administrador tiene todos), `.htaccess` que bloquea `app/`, `config/`, `database/`, `bin/` y `.env`.

Accesibilidad: enlace para saltar al contenido, foco visible, etiquetas en todos los campos, errores junto al campo y en un resumen enfocable, `aria-live` para la corrección, juegos operables con teclado, objetivos táctiles de 44 px o más, respeto a `prefers-reduced-motion`, contraste AA.

## Límites conocidos (dichos también en la portada)

- No envía recordatorios ni notificaciones; no es una app instalable y necesita conexión.
- El generador funciona mejor con textos informativos; con poemas o cuentos produce ejercicios pobres (por eso el docente los revisa antes de publicar).
- Un invitado que ingresa a una cuenta existente no fusiona su avance con ella.
- Sin recuperación de contraseña por correo (no hay servidor de correo en el piloto).
- El panel institucional para colegios está «en diseño».

## Créditos de imágenes

Portada: «Asset collection trip to Colombia», Peace Corps, CC0 · «juegos creativos», medea_material, CC BY 2.0 · «Free student texting class image», CC0 (rawpixel) · ©UNICEF/ECU/2020/Pintado, CC BY 2.0 · «Nivín. Maestra mostrando características de una tableta a estudiante», Escuela de Nivín, CC BY-SA 4.0 · «Docentes Wayuu», Neima Paz, CC0. Onboarding: además «Salonconestudiantes», Rodianchevere, CC BY-SA 3.0. Los créditos aparecen en el pie de la portada y en `public/assets/img/credits.json`.

Tipografías: Anton y Work Sans en la portada; Bebas Neue y EB Garamond en la app (Google Fonts, licencia OFL).

---

## Guion del video (máximo 5 minutos)

| Tiempo | Pantalla | Qué decir |
| --- | --- | --- |
| 0:00–0:30 | Portada | Problema: lo que se ve en clase se olvida. Hacer scroll para que el agua hunda el titular, responder la tarjeta real y mover el interruptor «Con repaso». |
| 0:30–1:30 | Onboarding como estudiante | Seis preguntas, la etiqueta que se llena, «Atrás», el resumen con «Cambiar». Cambiar los minutos y mostrar que cambia la cantidad de ejercicios. |
| 1:30–2:30 | Lección | Un error a propósito (vida y corrección), relacionar con teclado, ordenar letras, completar. Recargar la página a mitad: continúa donde iba. |
| 2:30–3:00 | Resultado y registro | XP, racha, errores; crear cuenta y ver que el avance se conserva. Mostrar «Mi cuenta»: descargar y borrar datos. |
| 3:00–4:15 | Docente | Pegar texto de ejemplo, revisar ejercicios (oración de origen), provocar un error de validación, publicar y obtener el código. Entrar con `profe.demo` a la clase `AGUA26`: pregunta más fallada, respuestas equivocadas, CSV. |
| 4:15–5:00 | Límites y cierre | Sin notificaciones ni modo sin conexión; el generador es por reglas y por eso se revisa; próximos pasos: recordatorios, panel por colegio. |
