# zalt-model
The Model component in MVC, classes for data reading, writing, metadata and translations.

The core of the zalt-model package are the **MetaModels**. These contain information about a datamodel, e.g. labels
and descriptions for data fields, but also preferred form element use and selection options, date formats, primary keys, 
etc...

The main elements of the zalt-model package are:

1. **MetaModels** that contain information about a DataModel and optional transformations on that data.
2. **DataModels** that handle the actual retrieval and storage of rows of data.
3. **Bridges** that generate view specific output (like a form or table) using the meta data.
4. Single row **dependencies** that change metamodel settings depending on the current row of data.
5. Multi row **Transformations** of the data, e.g. submodel loading, crosstabbing or union models.
6. **Types** combined multiple settings for a certain type of data, e.g. saving and displaying json data.


## MetaModels

MetaModels contain data about the data in DataModels, as well as optional transformations of that data.

The metadata can be set in multiple ways:

 - Using the `->set()` functions of the MetaModel.
 - Using metadata available in the DataModel, e.g. table information in an SQL model.
 - Using a custom function in the DataModel, e.g. an `applyFormatting()` function in the model.
 - Setting `linkedDefaults` in the applications `model` config file. 
 - Using `ModelTypeInterface` objects.

## DataModels

### Data loading

### Data saving

## Bridges

## (Row level) Dependencies

## Transformations

## Linked defaults

## Types
