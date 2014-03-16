UPGRADE FROM 1.0 to 1.1
=======================

General
-------

- Update the doctrine schema with php app/console doctrine:schema:update --force to :
 - remove the `fallback` field in the `Locale` entity
 - add the `properties` field in the `Attribute` entity
 - create the `pim_enrich_datagrid_view` and `pim_enrich_datagrid_configuration` tables

- Update the assets to get new js filters, css with `php app/console pim:install --task=assets --force`

CustomEntityBundle
------------------

The CustomEntityBundle has been moved in a dedicated repository to allow its own release cycle (contributions are, as always, welcomed).

The purpose of this bundle is to eases the creation of your own entities and related screen for a custom project.

If you are using the CustomEntityBundle, please add "akeneo/custom-entity-bundle" to your composer.json

FlexibleEntityBundle
--------------------

This bundle will be dropped in a future release, we began to prepare it by moving some code to the CatalogBundle and changing references.

Historically, it has been used for others implementations in OroPlatform (as the user entity), now it's only used for Product model.

By introducing the MongoDB support, we decided to make it more specific and so, more simple to understand, extend and maintain.

We do our best to let some temporary classes and methods and mark them as deprecated to let you some time to update your customizations.

To be consistent, services aliases / tags have been changed, if you use the following ones, please replace :
 - service `pim_flexibleentity.attributetype.factory` by `pim_catalog.factory.attribute_type`
 - tag `pim_flexibleentity.attributetype` by `pim_catalog.attribute_type`
 - service `@pim_flexibleentity.validator.attribute_constraint_guesser` by `@pim_catalog.validator.attribute_constraint_guesser`

You could also search for `@deprecated` and ensure that you use new methods to avoid future issues.

DataGridBundle
--------------

The version 1.1 introduces the support to MongoDB storage, the DataGrid bundle design has been re-worked when necessary to add this storage abstraction.

The `attribute_types.yml` file allows to configure column/filter/sorter of product values.

If you use it, please change it as following :

With 1.0 :
```
parameters:
    pim_datagrid.product.attribute_type.pim_catalog_identifier:
        column:
            type:        flexible_field
            selector:    flexible_values
        filter:
            type:        flexible_string
            parent_type: string
        sorter:          flexible_field
```

With 1.1 :
```
parameters:
    pim_datagrid.product.attribute_type.pim_catalog_identifier:
        column:
            type:        flexible_field
            selector:    flexible_values
        filter:
            type:        product_value_string
            parent_type: string
        sorter:          flexible_field
```