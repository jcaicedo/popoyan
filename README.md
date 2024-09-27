# Magento 2 Custom Modules

Este repositorio contiene una colección de módulos personalizados para Magento 2.4.x. Cada módulo está diseñado para
extender y mejorar diversas funcionalidades en Magento.

Para cada módulo en este repositorio, encontrarás un archivo README específico dentro de su directorio correspondiente.
Estos archivos README proporcionan instrucciones detalladas sobre la instalación, configuración y uso particular de cada
módulo.

## Lista de Módulos Disponibles

1. [Popoyan/CustomGraphQL](app/code/Popoyan/CustomGraphQL)
    - **Descripción:** Este módulo extiende el esquema de GraphQL en Magento para proporcionar endpoints personalizados.
    - **Instrucciones:** Consulta el archivo [README](app/code/Popoyan/CustomGraphQL/README.md) dentro del directorio
      del módulo para las instrucciones detalladas.

2. [Popoyan/AsyncCatalog](app/code/Popoyan/AsyncCatalog)
    - **Descripción:** Este modulo proporciona carga asíncrona de productos en la página de categoría para mejorar el
      rendimiento.
    - **Instrucciones:** Consulta el archivo [README](app/code/Popoyan/AsyncCatalog/README.md) dentro del directorio del
      módulo para las instrucciones detalladas.

3. [Popoyan/InventorySync](app/code/Popoyan/InventorySync)
    - **Descripción:** Es un modulo de una tarea cron que sincroniza automáticamente productos e inventarios desde una
      API externa.
    - **Instrucciones:** Consulta el archivo [README](app/code/Popoyan/InventorySync/README.md) dentro del directorio
      del módulo para las instrucciones detalladas.

## Instrucciones Generales

### Requisitos

- Magento 2.4.x
- PHP 8.2 o superior
- Composer

### Instalación General

Cada módulo puede tener pasos de instalación específicos que se detallan en sus respectivos archivos README. Sin
embargo, los pasos generales para instalar un módulo en Magento son:

1. **Descargar el Módulo:**
    - Coloque el módulo en el directorio `app/code/Vendor/ModuleName`.

2. **Registrar el Módulo:**
    - Ejecuta los siguientes comandos:
      ```bash
      php bin/magento setup:upgrade
      php bin/magento cache:flush
      ```

3. **Generar Código y Desplegar:**
    - Ejecuta los siguientes comandos:
      ```bash
      php bin/magento setup:di:compile
      ```
