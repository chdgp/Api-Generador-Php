# API Generator PHP

Este proyecto es un generador de APIs RESTful en PHP que proporciona una estructura organizada y modular para el desarrollo de servicios web. El proyecto incluye dos versiones (v2 y v3) con diferentes características y mejoras.

## Estructura del Proyecto

```
Api-Generador-Php/
├── v2/
│   ├── config/         # Configuraciones del sistema
│   ├──── documentation/ # Documentación de la API
│   ├── module/         # Módulos de la API
│   ├── .htaccess      # Configuración de Apache
│   └── index.php      # Punto de entrada principal (creador de API)
├── v3/
│   ├── business/      # Lógica de negocio
│   ├── config/        # Configuraciones del sistema
│   ├── documentation/ # Documentación de la API
│   ├── module/        # Módulos de la API
│   ├── .htaccess     # Configuración de Apache
│   └── index.php     # Punto de entrada principal (creador de API)
└── htaccess.txt      # Plantilla de configuración Apache
```

## Requisitos del Sistema

- PHP 7.4 o superior
- Servidor web Apache con mod_rewrite habilitado
- MySQL/MariaDB

## Instalación

1. Clona el repositorio:
```bash
git clone https://github.com/tu-usuario/Api-Generador-Php.git
```

2. Configura tu servidor web para apuntar al directorio del proyecto

3. Copia y renombra el archivo `htaccess.txt` a `.htaccess` en la raíz del proyecto (opcional)

4. Configura los parámetros de conexión a la base de datos en el directorio `config`

## Versiones Disponibles

### Versión 2 (v2)
- Estructura básica de API RESTful
- Documentación integrada
- Sistema de módulos
- Configuración centralizada
- Manejo de rutas básico

### Versión 3 (v3) php 8.1 o superior
- Arquitectura mejorada
- Capa de negocio separada
- Documentación integrada
- Sistema de módulos mejorado
- Mayor seguridad y optimización

## Uso

1. Selecciona la versión que deseas utilizar (v2 o v3)
2. Configura los parámetros necesarios en el directorio `config`
3. Accede a el Punto de entrada principal (creador de API) en la raíz del proyecto

## Documentación

La documentación detallada se encuentra en el directorio `v3/documentation/`
La documentación detallada se encuentra en el directorio `v2/config/documentation/` (old version)

## Contribución

Las contribuciones son bienvenidas. Por favor, asegúrate de:

1. Hacer fork del proyecto
2. Crear una rama para tu característica
3. Hacer commit de tus cambios
4. Enviar un pull request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo LICENSE para más detalles.