RedBean_ReBean (RevisionBean)
=======================

This is a plugin for the [RedBeanPHP ORM](http://www.redbeanphp.com/), which
will be generating automatically revision tables for given Beans

This is achieved by creating a table named after your bean plus the "revision" prefix.
The table contains all the columns from your bean. Additionally it has a column "action" specifying
if this revision was made by an [INSERT,UPDATE,DELETE] statement.
Also included is the column "original_id" which represents the ID of the bean, that's been
revisioned. Finally there is a "lastedit" column indicating when the change happened.

All the functionality is achieved by using AFTER Triggers

Current status:
The plugin so far works for MySql

Update:
=======================
Jan 16, 2020
- Updated to support RedBean 5.4

Nov 3, 2013
- Now uses the R::ext plugin helper so you can access revisioning without any previous
  instance creation. Just use R::createRevisionSupport($YOURBEAN);
- Added PHPUnit Tests
- Throw Exception if Bean is already under revision support


Usage:
=======================

- Download the latest version of [RedBean](https://redbeanphp.com/download)
- Add the file ReBean.php to your site.
- Create your first bean type
- Store it in the DB `(R::store($YOURBEAN))`
- Call the revision method like this

```php
   R::createRevisionSupport($YOURBEAN);
```
- Happy modifying of your previous Bean. You should be able to see all changes
  in the created revision table

Example:
=======================

Take a look at the included example.php.
