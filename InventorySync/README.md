# SyncProducts

SyncProducts es una tarea cron que sincroniza automáticamente productos e inventarios desde una API externa con tu
tienda Magento 2.

## Tabla de Contenidos

- [Descripción](#descripción)
- [Instalación](#instalación)
- [Uso](#uso)
- [Configuración](#configuración)
- [Pruebas](#pruebas)
- [Contribuciones](#contribuciones)
- [Licencia](#licencia)

## Descripción

SyncProducts se encarga de descargar datos de productos e inventarios desde una API externa y los sincroniza con la base
de datos de Magento. Esta tarea garantiza que la información de los productos se mantenga actualizada automáticamente,
mejorando la eficiencia de la gestión del inventario.

## Instalación

Para instalar SyncProducts en tu instancia de Magento 2, sigue estos pasos:

1. Clona este repositorio en tu directorio de módulos de Magento.

    ```bash
    git clone <repository-url> app/code/Popoyan/InventorySync
    ```

2. Habilita el módulo:

    ```bash
    bin/magento module:enable Popoyan_InventorySync
    ```

3. Ejecuta las actualizaciones de base de datos:

    ```bash
    bin/magento setup:upgrade
    ```

4. Limpia las cachés:

    ```bash
    bin/magento cache:clean
    ```

5. Asegúrate de que el cron de Magento esté configurado y funcionando.

## Uso

SyncProducts se ejecuta como una tarea cron dentro de Magento. La sincronización se activa automáticamente en base al
cron de Magento. No se requiere intervención manual.

### Ejecución Manual

Si deseas ejecutar la sincronización manualmente, puedes hacerlo desde la línea de comandos:

```bash
bin/magento cron:run --group="default"
```

## Configuración

SyncProducts toma configuraciones desde el panel de administración de Magento.

1. Navega a: `Stores -> Configuration -> Inventory Sync`.

2. Configura los siguientes parámetros:

    - **API URL**: La URL desde donde se obtienen los datos del inventario.
    - **Enable Sync**: Habilita/deshabilita la sincronización de inventario.

## Prueba con Comando

Se ha habilitado un comando para realizar prueba en tiempo real.

```bash
bin/magento popoyan-inventory-sync:execute
```
