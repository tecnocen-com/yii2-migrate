Yii2 Migrate Tools
==================

This library eases the creation of normalized databases by providing classes to
create tables separatedly with a simple logic.

Instalation
-----------

You can use composer to install the library `tecnocen/migrate` by running the
command;

`composer require tecnocen/migrate`

or edit the `composer.json` file

```json
require: {
    "tecnocen/migrate": "*",
}
```


Usage
-----

You can use the `tecnocen\migrate\CreateTableMigration` to generate different
type of migration tables to be used by each of your table types.

For example lets say you want to save who and when edits and creates each the
entities on your system but your pivot tables only require to know when they
were created since they can't be edited.

```php
abstract class EntityTable extends \tecnocen\migrate\CreateTableMigration
{
    public function defaultColumns()
    {
        return [
            'created_at' => $this->datetime()->notNull(),
            'updated_at' => $this->datetime()->notNull(),
            'created_by' => $this->normalKey(),
            'updated_by' => $this->normalKey(),
        ]          
    }

    public function defaultForeignKeys()
    {
        return [
            'created_by' => 'user',
            'updated_by' => 'user',
        ];
    }
}

abstract class PivotTable extends \tecnocen\migrate\CreateTableMigration
{
    public function defaultColumns()
    {
        return [
            'created_at' => $this->datetime()->notNull(),
        ]          
    }
}
```

Then you can use them separatedly for each type of table you have


```php
class m170101_010101_ticket extends EntityTable
{
     public function columns()
     {
         return [
             'id' => $this->primaryKey(),
             'project_id' => $this->normalKey(),
             'title' => $this->string(150)->notNull(),
             'description' => $this->text(),
         ];
     }

     public function foreignKeys()
     {
         return ['project_id' => ['table' => 'project']];
     }
}

class m17010101_010102_ticket_employee extends PivotTable
{
     public function columns()
     {
         return [
             'ticket_id' => $this->normalKey(),
             'employee_id' => $this->normalKey(),
         ];
     }

     public function foreignKeys()
     {
         return [
             'ticket_id' => ['table' => 'ticket'],
             'employee_id' => ['table' => 'employee'],
         ];
     }

     public function compositePrimaryKeys()
     {
         return ['ticket_id', 'employee_id'];
     }
}
```

When running the migration `m170101_010101_ticket` it will generate a table
with the columns

- `id`,
- `project_id`,
- `title`,
- `description`,
- `created_by`,
- `updated_by`
- `created_at`,
- `updated_at`

With primary key `id` and three foreign keys linked to the columns `project_id`,
`created_by` and `updated_by`.

And the migration `m170101_010101_ticket_employee` will generate a table with
the  columns.

- `ticket_id`
- `employee_id`
- `created_at`

With a composite primary key of the columns `ticket_id` and `employee_id` and
two foreign keys linked to the previous two columns.
