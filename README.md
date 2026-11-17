# HTTP Lab — Laboratorio de Seguridad Web intencionalmente vulnerable

> ⚠️ **ADVERTENCIA:** esta aplicación es **intencionalmente vulnerable** y existe con fines **exclusivamente educativos**.
> Úsala solo en `localhost` o en una red de laboratorio aislada, en un entorno controlado y con autorización.
> **Nunca la expongas a Internet.** Todos los datos y credenciales son ficticios.

Laboratorio a medida para un curso universitario sobre el protocolo HTTP y la seguridad de aplicaciones web, con **Burp Suite** como herramienta principal (Proxy, Repeater, Intruder, Decoder, Comparer y, opcionalmente, Turbo Intruder). El foco está en **vulnerabilidades de lógica de negocio** en sectores reales (banca, retail, fintech), además de reconocimiento y criptografía aplicada.

## Laboratorios

| Categoría | ID | Laboratorio | Sector | Dificultad |
|---|---|---|---|---|
| 🧠 Lógica de negocio | BL-01 | Transferencia con monto manipulado | Banca | Básico |
| | BL-02 | IDOR en estado de cuenta | Banca | Básico |
| | BL-03 | Manipulación de precio y cupones en checkout | Retail | Intermedio |
| | BL-04 | Bypass de límite diario de retiro | Banca | Intermedio |
| | BL-05 | Salto de verificación OTP en flujo multi-paso | Banca/Fintech | Avanzado |
| | BL-06 | Race condition: doble canje de cupón | Fintech | Avanzado |
| 🔎 Reconocimiento | RC-01 | Fugas en el código fuente | Corporativo | Básico |
| | RC-02 | Descubrimiento de contenido y archivos expuestos | Corporativo | Intermedio |
| 🔐 Criptografía | CR-01 | Cookie de sesión débil | Fintech | Intermedio |
| | CR-02 | JWT con firma débil / alg:none | Fintech | Avanzado |

Las categorías son independientes (sin orden obligatorio). Cada lab tiene 3 pistas escalonadas y un campo de verificación de flag.

## Requisitos

- Docker Engine / Docker Desktop con Docker Compose v2.
- Burp Suite Community o Professional (para los alumnos).

## Cómo levantarlo

```bash
docker compose up --build -d
```

Abre **http://localhost:8080**, identifícate con tu nombre y elige un laboratorio.

> **Conflicto de puertos con Burp:** Burp escucha por defecto en `127.0.0.1:8080`. Si el laboratorio y Burp corren en el mismo equipo, cambia uno de los dos:
> ```bash
> LAB_PORT=8000 docker compose up --build -d      # lab en http://localhost:8000
> ```
> o configura el listener de Burp en `127.0.0.1:8081`. La página **«¿Cómo configuro Burp?»** del portal lo explica, junto con el detalle de que los navegadores no envían `localhost` al proxy por defecto (usa el navegador integrado de Burp).

Alternativa sin Compose:

```bash
docker build -t http-lab .
docker run -d --name http-lab -p 127.0.0.1:8080:80 -v labdata:/var/www/app/data \
  -e LAB_SECRET='cambia-esto' -e TEACHER_PASSWORD='otra-clave' http-lab
```

### Variables de entorno

| Variable | Por defecto | Uso |
|---|---|---|
| `LAB_SECRET` | `dev-secret-cambiame` | Secreto con el que se derivan las flags. **Cámbialo en cada curso.** |
| `TEACHER_PASSWORD` | `profesor` | Contraseña del panel `/profesor.php`. |
| `LAB_PORT` | `8080` | Puerto del host. |
| `LAB_BIND` | `127.0.0.1` | Interfaz del host. Usa `0.0.0.0` **solo** en una red de laboratorio aislada (p. ej. para que los alumnos usen el servidor del profesor). |

Puedes definirlas en un archivo `.env` junto a `docker-compose.yml`.

## Cómo funciona

### Alumnos y flags dinámicas
- En `login.php` el alumno ingresa su nombre y recibe un **token** (12 hex) que identifica su progreso. Con ese token puede retomar desde otro navegador.
- La flag de cada lab es `FLAG{ HMAC_SHA256(LAB_SECRET, token + ":" + lab_id)[0:16] }`: distinta por alumno, no se almacena y se recalcula al verificar.
- La flag solo aparece en la respuesta HTTP de la acción que es posible **tras explotar** la vulnerabilidad.
- La verificación es siempre en el servidor (`POST /api/verify.php` → `src/flags.php`), con límite de 10 intentos por minuto.

### Pistas
3 por lab, de conceptual a casi-solución (nunca la flag). Se desbloquean una a una (`POST /api/hint.php`) y se registra cuántas usó cada alumno. Se editan en `src/labs.php`.

### Panel del profesor
`http://localhost:8080/profesor.php` (contraseña `TEACHER_PASSWORD`): matriz alumnos × labs con estado, tiempo desde la primera visita hasta completar y pistas usadas; exportación CSV; reinicio o eliminación de alumnos.

Ver las flags esperadas de un alumno (soporte en clase):
```bash
docker compose exec lab php seed/flags-alumno.php <student_token>
```

## Cómo resetear

| Qué | Cómo |
|---|---|
| Estado de **un** lab (saldo, carrito, cupón…) | Botón «↺ Reiniciar estado del laboratorio» dentro del lab. No borra progreso ni pistas. |
| **Todo** el progreso de un alumno | Botón «Reiniciar todo mi progreso» en el portal, o desde el panel del profesor. |
| **Toda** la base (todos los alumnos) | `docker compose exec lab php seed/init.php --reset` |
| Volver a cero absoluto (borra el volumen) | `docker compose down -v && docker compose up --build -d` |

El script `seed/init.php` es idempotente y se ejecuta en cada arranque del contenedor.

## Estructura

```
├── docker-compose.yml / Dockerfile / docker/   # imagen todo-en-uno (php:8.2-apache + SQLite)
├── public/                 # document root de Apache (lo único accesible por web)
│   ├── index.php           # portal (categorías, tarjetas, progreso)
│   ├── login.php           # identificación del alumno
│   ├── help-burp.php       # guía de configuración de Burp
│   ├── profesor.php        # panel del profesor
│   ├── api/                # verify.php, hint.php, reset.php (comunes a todos los labs)
│   ├── assets/             # css + js del portal
│   └── labs/               # los 10 laboratorios (bl01-… a cr02-…)
├── src/                    # lógica común: config, db, auth, flags, hints, progress, labs, layout
├── seed/                   # init.php (BD idempotente) + flags-alumno.php (CLI profesor)
├── data/                   # SQLite (volumen persistente)
└── solutions/              # writeups SOLO profesor — fuera del docroot y fuera de la imagen
```

> Los writeups (`/solutions`) no se publican en este repositorio. Si eres docente, solicítalos al autor.

Cada laboratorio comenta en su código la línea vulnerable con `⚠️ VULN`.

## Seguridad del entorno

- Vulnerabilidades **intencionales, acotadas y documentadas**, limitadas a la lógica de cada lab. No hay ejecución de comandos del sistema, subida de archivos, backdoors ni conexiones salientes.
- Por defecto el contenedor solo escucha en `127.0.0.1`.
- `/solutions`, `/src`, `/seed` y `/data` no son accesibles vía web; los archivos auxiliares `_*.php` están denegados por Apache.
- El contenedor es efímero y reseteable (ver «Cómo resetear»).
- El propósito es la **formación en un entorno controlado y con autorización**. Aplicar estas técnicas contra sistemas sin permiso explícito es ilegal.
