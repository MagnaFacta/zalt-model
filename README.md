# zalt-model
The Model component in MVC, classes for data reading, writing, metadata and translations.

The core of the zalt-model package are the **metamodels**. These contain information about a datamodel, e.g. labels
and descriptions for data fields, but also preferred form element use and selection options, date formats, primary keys, 
etc...

The main elements of the zalt-model package are:

1. **Metamodels** that contain information about a datamodel.
2. **Datamodels** that handle the actual retrieval and storage of rows of data.
3. **Bridges** that generate view specific output (like a form or table) using the meta data.
4. Single row **dependencies** that change metamodel settings depending on the current row of data.
5. Multi row **Transformations** of the data, e.g. submodel loading, crosstabbing or union models.
6. **Types** combined multiple settings for a certain type of data, e.g. saving and displaying json data.


## Metamodels

## Datamodels

### Data loading

### Data saving

## Bridges

## (Row level) Dependencies

## Transformations

## Types
