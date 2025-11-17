# About the Zalt libraries

## About the name Zalt

The name Zalt used to stand for Zend framework ALTernative. The Zend Framework is an old PHP development framework
that was used when we produced the first versions of what became these libraries. These days we use the name as-is. 


## The problems the Zalt libraries try to solve

### Problem 1 MVC modularization

The MVC pattern for web development is one of the most used within web development. However: beyond specifying that 
there is a controller/handler object that uses a model to do something with the data in that model using several 
different views. The only "modules" specified for the code are there for the controller/handler object and the data
storage object. The rest is up to the programmer.

This is an unsatisfying state as different views can and **should** share a lot of code. For example:

1. Different views for a model should use the same textual information on model fields, like labels and descriptions.
1. Different views for a model should use the same type information, like field length, data type and formatting. 
1. Similar views in different controllers should reuse the same code, using the different meta-data.
1. Most controllers should re-use the same views: browse/search for items / show/create/edit/delete item.
1. But these days we should alse be able to use this information in a json web api, providing both item-data and meta-data.

The Zalt libraries provide (independently usable) modules to increase code reuse and ease of programming:

1. The **meta models** contain meta-data shared among different views and API's.
2. The **HTML snippets** contain (parts of) default views that can be extended / adapted for a specific controller

### Problem 2 Reusing information. Solution: Meta-models

The label of a name field in a database, can be "name" or "naam" or "nom" or "名前" depending on the language in use. 
If the label of this field differs in different views, say the edit and show fields, this will usually confuse the
end users. If it is translated only in some views, but not in others this is a problem.

The user will also be confused when the name is shown first in the show view and last in the edit view. The order of 
display should therefore also be fixed as much as practicable.  

The solution is to add a meta-model to each data-model containing this information. The meta-model can be the same for 
different (types) of data-model, but for one controller the information should be set centrally in one place: either 
in the code the controller/handler object or in a specific sub-model of a data-model.

### Problem 3 Reusing views. Solution HTML Snippets 

Most (but not all) data models have standard browse/show/create/edit/delete views that can be generated using 
information in a meta-model. The specfic information used / needed depends on the view / libraries you are 
using. In other words: the meta-model does not determine what is stored in it, but the view determines what information
is used / required. For standard views we usually use bridge objects like table bridges or form bridges that use the 
meta-model to determine what to display.

Several templating systems exist for PHP and of course these work fine if you are used to them. In most you will be able
to generate a generic display and even form template for basic views. However: in any and all 
cases they require you to switch languages within your application and most templating systems leave program flow to
other locations. E.g. a form template can be used to display a form, but logic for validation, saving and re-routing 
after completion is not in a seperate module.

Instead of a template system - or in conjunction with one - the Zalt libraries generate HTML output using basic
HTML classes for code like `$table->row()->td('Zalty isn\'t it?')`. This module generates HTML and works with basic
knowledge of the structure of HTML. However: it does not solve the logic problem.

Validation, redirection, disabling/enabling the display of groups of HTML output is solved using snippet objects. Again
these are very basic objects with a constructor based ServiceManager dependency. They have a limited routing capacity 
in that they can return HTML output (or nothing) as well as alternative response objects or redirect urls. This enables 
validation, saving and output or redirection to be implemented in a single object.  

### Problem 4 Offering default functionality without limiting the freedom of programmers

Having default views is fine for most standard tables, but not every complex user requirement can be met using 
default solutions. So all Zalt libraries are designed to be flexible in use and extension.

You can make a default controller/handler object (or use the one from the HTML Snippets library), but usually some views will 
need additional code. The Zalt libraries offer you great freedom in implementing these changes:

1. You can change the meta-model for a specific view.
2. You can use a **dependency** to change meta-model settings depending on a value in the data. (Say for a sub-select.) 
3. You can use a **transformer** to change the form of the data. (Say a crosstab view.)
2. You can use (an)other snippet(s) for a certain view.
3. By extending an existing snippet you can add your code without losing the existing functionality.
