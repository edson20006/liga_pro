# Liga Pro - Sistema de Gestion de Liga de Futbol

Aplicacion web desarrollada en PHP + MySQL para administrar una liga de futbol amateur/profesional.

Incluye gestion de equipos, jugadores, partidos, resultados, goles, tarjetas, entrenadores y modulos administrativos con control de acceso por rol.

## Tecnologias

- PHP (mysqli)
- MySQL / MariaDB (XAMPP)
- Bootstrap 5
- CSS personalizado

## Estructura del proyecto

- index.php: panel principal con metricas.
- login.php: acceso al sistema.
- logout.php: cierre de sesion.
- change_password.php: cambio obligatorio de clave al primer ingreso.
- funciones.php: utilidades globales (auth, csrf, helpers, permisos).
- conexion.php: conexion local a BD (no versionar).
- conexion.example.php: plantilla segura de conexion para compartir.
- equipos.php: gestion de equipos y entrenadores.
- jugadores.php: gestion de jugadores (alta y eliminaciones).
- partidos.php: programacion y listado de partidos.
- resultados.php: gestion de goles, tarjetas y finalizacion de partidos.
- administracion.php: roles, usuarios legacy, patrocinadores y vinculos equipo-patrocinador (solo admin).

## Funcionalidades principales

### 1. Autenticacion y seguridad

- Login contra tabla usuarios_auth.
- Sesiones PHP con regeneracion de id al autenticar.
- Cambio de clave obligatorio al primer acceso.
- CSRF en todos los formularios POST.
- Permisos de administracion restringidos a usuarios admin.

### 2. Modulo de Equipos

- Registrar equipos.
- Eliminar equipos.
- Gestion de entrenadores dentro del mismo modulo.

### 3. Modulo de Jugadores

- Registrar jugadores.
- Eliminar jugador individual.
- Eliminar plantilla completa por equipo.

### 4. Modulo de Partidos

- Programar partidos.
- Validacion para impedir local = visitante.
- Visualizacion del calendario de partidos.

### 5. Modulo de Resultados

- Registrar goles con validacion de pertenencia del jugador al partido.
- Registrar tarjetas desde resultados.
- Eliminar tarjetas desde resultados.
- Finalizar partido.
- Listado de ultimos goles y tarjetas.

### 6. Administracion (solo admin)

- Gestion de roles.
- Gestion de usuarios legacy (tabla usuarios) sincronizados con usuarios_auth para login.
- Gestion de patrocinadores.
- Gestion de vinculos equipo-patrocinador.

## Requisitos

- XAMPP con Apache y MySQL activos.
- PHP 8 recomendado.
- Base de datos creada en MySQL (liga_futbol_pro).

## Instalacion local

1. Copiar el proyecto en htdocs:

   `C:\xampp\htdocs\liga_pro`

2. Crear y configurar conexion local:

   - Copiar conexion.example.php a conexion.php
   - Editar credenciales locales en conexion.php

3. Crear/importar base de datos:

   - Crear BD: liga_futbol_pro
   - Ejecutar scripts/db_migration_liga_pro.sql para asegurar estructura minima

4. Crear usuario admin inicial:

   - Abrir en navegador: http://localhost/liga_pro/seed_admin.php
   - Usuario inicial: admin
   - Clave temporal: Admin123!

5. Ingresar al sistema:

   - http://localhost/liga_pro/login.php

## Respaldo de base de datos

Ejecutar en PowerShell desde la raiz del proyecto:

`powershell -ExecutionPolicy Bypass -File .\scripts\backup_liga_pro.ps1`

Genera un .sql con timestamp en la carpeta de backups configurada en el script.

## Base de datos y diagrama ER

Para exportar estructura y generar un diagrama ER externo, se recomienda usar mysqldump con --no-data.

Script base disponible:

- scripts/db_migration_liga_pro.sql

## Consideraciones de seguridad para GitHub

No subir al repositorio:

- conexion.php
- seed_admin.php
- backups y dumps SQL

Ya existe un .gitignore con exclusiones recomendadas.

## Flujo de usuarios recomendado

- Usuario admin: puede ver Administracion y gestionar catalogos.
- Usuario operativo (no admin): gestiona modulos funcionales (equipos, jugadores, partidos, resultados) sin acceso a Administracion.

## Estado actual

Proyecto funcional para gestion interna de liga, con capas basicas de seguridad aplicadas y base preparada para evolucionar a:

- permisos por rol mas granulares,
- auditoria de cambios,
- reportes estadisticos,
- API futura.
