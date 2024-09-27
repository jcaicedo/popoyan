# Vendor_CustomGraphQL

Este módulo extiende el esquema de GraphQL en Magento 2.4.x para proporcionar un nuevo endpoint que devuelve información
personalizada de los productos, incluyendo el nombre, precio y cantidad de inventario disponible. El endpoint requiere
autenticación y soporta paginación para manejar catálogos grandes de productos.

## Requisitos

- Magento 2.4.x
- PHP 8.2 o superior
- Composer

## Instalación

### 1. Descargar el Módulo

Ve a la carpeta raíz de tu instalación de Magento y ejecuta el siguiente comando para descargar el módulo:

```bash
mkdir -p app/code/Popoyan/CustomGraphQL
```

Luego, coloca los archivos del módulo en `app/code/Popoyan/CustomGraphQL`.

### 2. Registrar el Módulo

Registra el nuevo módulo ejecutando los siguientes comandos:

```bash
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### 3. Generar Código y Desplegar

Ejecuta los siguientes comandos para generar el código necesario y desplegar el contenido estático:

```bash
php bin/magento setup:di:compile

```

## Configuración

No se requiere configuración adicional para este módulo. Una vez instalado, el endpoint de GraphQL estará disponible y
requerirá autenticación.

## Uso

### Autenticación

Primero, debes obtener un token de autenticación utilizando las credenciales de un usuario registrado en Magento.

#### Obtener el Token de Cliente

Ejecuta la siguiente mutación en GraphiQL para obtener el token:

```graphql
mutation {
    generateCustomerToken(email: "your-email@example.com", password: "your-password") {
        token
    }
}
```

### Consulta de Productos Personalizada

Usa el token obtenido en la cabecera de tu solicitud para ejecutar la consulta GraphQL. En GraphiQL, abre la pestaña de
cabeceras (Headers) y añade:

```json
{
    "Authorization": "Bearer <YOUR_TOKEN>"
}
```

Luego, ejecuta la siguiente consulta:

```graphql
query {
    customProductInfo(skus: ["SKU1", "SKU2"], pageSize: 10, currentPage: 1) {
        items {
            name
            price
            qty
        }
        total_count
        page_info {
            page_size
            current_page
        }
    }
}
```

### Ejemplo de Respuesta

```json
{
    "data": {
        "customProductInfo": {
            "items": [
                {
                    "name": "Product 1",
                    "price": 100.0,
                    "qty": 20.0
                },
                {
                    "name": "Product 2",
                    "price": 150.0,
                    "qty": 15.0
                }
            ],
            "total_count": 2,
            "page_info": {
                "page_size": 10,
                "current_page": 1
            }
        }
    }
}
```

## Decisiones Técnicas

- **Autenticación Obligatoria**: El resolver verifica que el usuario esté autenticado utilizando
  `GraphQlAuthorizationException`. Esto asegura que solo usuarios autorizados puedan acceder a la información del
  producto.
- **Paginación**: El endpoint soporta paginación para manejar catálogos grandes de productos, mejorando el rendimiento y
  la usabilidad.
- **Estructura Modular**: La implementación sigue la estructura de módulos de Magento, permitiendo una fácil extensión y
  mantenimiento.

## Limitaciones Conocidas

- **Validación de SKUs**: Actualmente, la consulta no valida si todos los SKUs proporcionados existen antes de intentar
  obtener la información de los productos. Esto puede resultar en excepciones si se proporcionan SKUs inválidos.
- **Optimización de Consultas**: La consulta actual puede estar limitada en términos de rendimiento cuando se maneja un
  número muy grande de SKUs simultáneamente. Se pueden realizar mejoras adicionales para optimizar estas consultas.

