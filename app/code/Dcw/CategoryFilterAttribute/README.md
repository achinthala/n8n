# Dcw_CategoryFilterAttribute

This module adds a new field to the category edit pages in the backend, allowing you to specify which attributes
and the values for those attributes should be excluded from the layered navigation.

### Task reference

* [INS-5164](https://dotcomweavers.atlassian.net/browse/INS-5164)

### Settings:

1. Go to the backend, Catalog -> Categories
2. Select the category that you want to customize.
3. Under the General field set find the input "Exclude attribute from layered navigation"
4. The setting should be as follows:

attribute_code_1(label a, label b) | attribute_code_2(label c )

This means that you can set multiple attributes with multiples values, keeping in mind that
attributes should be separate by pipe character and if the attribute has more than one value that you
want to exclude, separate those values with coma.
