# AsyncCatalog

Este módulo de Magento 2 proporciona carga asíncrona de productos en la página de categoría para mejorar el rendimiento
y la experiencia del usuario.

## Requisitos

- Magento 2.3.0 o superior
- PHP 8.2 o superior

## Instalación

Sigue estos pasos para instalar el módulo `Popoyan_AsyncCatalog`:

1. **Clonar el repositorio en tu directorio de Magento:**

   ```sh
   cd /path/to/magento/app/code
   mkdir -p Popoyan/AsyncCatalog
   cd Popoyan/AsyncCatalog
   git clone <URL_DEL_REPOSITORIO> .
   ```

2. **Habilitar el módulo:**

   ```sh
   bin/magento module:enable Popoyan_AsyncCatalog
   bin/magento setup:upgrade
   bin/magento cache:clean
   bin/magento cache:flush
   ```

## Configuración

No se requiere configuración adicional. El módulo cargará los productos de forma asíncrona en la página de categoría
automáticamente.

## Pruebas

### Verificar la Ruta AJAX

1. Accede a la URL del controlador AJAX en tu navegador para comprobar si devuelve los datos de los productos. Reemplaza
   `<tu_tienda_magento>` con la URL de tu tienda Magento y `<category_id>` con el ID de una categoría que exista en tu
   tienda:
    ```sh
    https://<tu_tienda_magento>/asynccatalog/category/ajaxProducts?category_id=<category_id>
    ```
2. Deberías ver una respuesta JSON con los datos de los productos de la categoría especificada.

### Probar en el Frontend

1. Abre las herramientas de desarrollo de Google Chrome (F12) y navega a la pestaña "Network" (Red).
2. Filtra por "XHR" para ver las solicitudes AJAX.
3. Navega a una página de categoría en tu tienda.
4. Verifica que se realiza una solicitud AJAX a `/asynccatalog/category/ajaxProducts` y que la respuesta contiene los
   productos.
5. Asegúrate de que los productos se muestran correctamente en la página.

## Decisiones Técnicas

### Controlador AJAX

Se creó un controlador para manejar las solicitudes AJAX y devolver los datos de los productos en formato JSON. Esto
permite cargar los productos de manera asíncrona cuando se accede a una página de categoría.

### Carga de Productos

Los productos se cargan utilizando una colección de productos filtrada por categoría. Los atributos `name`, `price`, e
`image` se seleccionan explícitamente para asegurarse de que la información necesaria se obtiene correctamente.

```php
$productCollection = $this->productCollectionFactory->create()
    ->addAttributeToSelect(['name', 'price', 'image'])
    ->addCategoryFilter($category);
```

### Renderización de Productos

Un archivo de plantilla (`products.phtml`) y un script JavaScript (`ajaxProducts.js`) se utilizaron para manejar la
inserción de los productos en el DOM una vez que se completa la solicitud AJAX.

## Limitaciones Conocidas

- **Rendimiento de la Base de Datos**: Si la categoría contiene una gran cantidad de productos, la consulta a la base de
  datos puede ser lenta. Se recomienda optimizar los índices de la base de datos y asegurarse de que el servidor de la
  base de datos está adecuadamente configurado.

- **Imágenes de Productos**: Si los productos no tienen una imagen asignada, se mostrará una imagen de marcador de
  posición. Asegúrate de que todos los productos tengan imágenes válidas para mejorar la experiencia del usuario.

- **Compatibilidad de Temas**: El módulo ha sido probado con el tema Luma de Magento. Si utilizas un tema personalizado,
  puede ser necesario ajustar los selectores CSS y la estructura del HTML en el archivo `products.phtml` y
  `ajaxProducts.js`.


