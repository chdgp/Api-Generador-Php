
# Generación de APIS

Crea un CRUD por Api colocando el nombre de la carpeta y seleccionado la tabla de la base de datos 
NOTA: Las carpetas se crean en v1/module/* automaticamente 

## Environment Variables

``` code
v2\config\init.php => file add
```

``` php
$dbconect['DB_PROD'];// => Base de datos MySql
$dbconect['PASS_PROD'];// => Contraseña Base de datos MySql
$dbconect['USER_PROD'];// => Usuario Base de datos MySql
$dbconect['HOST_PROD'];// => Servidor ip o localhost
```

---
---

# Generación de Documentación de API

## Resumen

Esta documentación proporciona los pasos necesarios para generar la documentación de los endpoints de la API. Es fundamental seguir estos requisitos y pasos para garantizar que la documentación se genere correctamente y de manera segura.

## Requisitos

1. **Endpoints Creado**: Todos los endpoints de la API deben estar creados.
2. **Cache de Endpoints**: Debe existir una caché de los endpoints, lo que implica que al menos uno de los endpoints debe haber sido consultado previamente.

## Pasos para Generar la Documentación

### Paso 1: Obtener el Token

Para generar la documentación, primero necesitas obtener el token CSRF. Puedes hacerlo realizando una solicitud a la siguiente URL:

``` code
GET v2/config/util.model.php?mode=getToken
```
### Paso 2: Generar la Documentación

Una vez que tengas el token, puedes generar la documentación de los endpoints usando la siguiente URL:

``` code
GET v2/config/documentation/?token={value}
```
Reemplaza `{value}` con el token obtenido en el Paso 1.

### Paso 3: Seguridad

Por razones de seguridad, create archivo `key.js` asegúrate de modificar los siguientes valores en tu configuración:

```javascript
var USER_PLAY = '';
var PASS_PLAY = '';
```

Nota: Ej => `v2/config/documentation/key.example.js`
