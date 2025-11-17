# zalt-model
The Model component in MVC, classes for data reading, writing, metadata and translations. See 
[About Zalt](docs/ABOUT-ZALT.md) for more information on Zalt libraries in general.

The core of the zalt-model package are the **MetaModels**. These contain information about a datamodel, e.g. labels
and descriptions for data fields, but also preferred form element use and selection options, date formats, primary keys, 
etc...

The main elements of the zalt-model package are:

1. **MetaModels** that contain information about a DataModel and optional transformations on that data.
2. **DataModels** that handle the actual retrieval and storage of rows of data.
3. **Bridges** that generate view specific output (like a form or table) using the metadata.
4. Single row **Dependencies** that change metamodel settings depending on the current row of data.
5. Multi row **Transformations** of the data, e.g. submodel loading, crosstabbing or union models.
6. **Types** combined multiple settings for a certain type of data, e.g. saving and displaying json data.


## MetaModels

MetaModels contain data about the data in DataModels, as well as optional transformations of that data.

### Setting the meta data

The metadata can be set in multiple ways:

 - Using the `->set()` functions of the MetaModel.
 - Using metadata available in the DataModel, e.g. table information in an SQL model.
 - Using a custom function in the DataModel, e.g. an `applyFormatting()` function in the model.
 - Using `ModelTypeInterface` objects, both directly and as default types.
 - Lastly Dependencies change the metadata depending on the current values of a row. 

### Transforming data after loading

After loading the raw data the MetaModel is used to change that data:

1. Transformers use the full data set (for crosstabbing, submodels, unions, etc...) and are executed on the loaded data (in `processAfterLoad()`)
2. Then in `processRowAfterLoad()` any `setOnLoad()` functions are called first. Those function change single data fields, e.g. `DateTime` strings are turned into objects.
3. In the third step `processRowAfterLoad()` handles any dependencies that are used to change model settings, e.g. changing the `multiOptions` set for a field. 

### Transforming data before saving

The MetaModel can also change the data before and after saving:

1. In `processBeforeSave()` any transformers are used on the data (e.g. setting key values for new sub-rows).
2. Then in `processRowBeforeSave()` the `setOnSave()` function are called for individual field data, e.g. changing a `DateTime` object into a string.
3. After actually saving the data `processRowAfterLoad()` is called to process the output data of the `save()` function.


## DataModels

### Data loading

### Data saving

## Bridges

## (Row level) Dependencies

## Transformations

## Types
