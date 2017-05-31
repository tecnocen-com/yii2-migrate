<?php

namespace tecnocen\migrate;

use yii\helpers\ArrayHelper;

/**
 * Handles the creation for one table.
 *
 * @author Angel (Faryshta) Guevara <aguevara@alquimiadigital.mx>
 * @author Fernando (PFernando) López <flopez@alquimiadigital.mx>
 */
abstract class CreateTableMigration extends \yii\db\Migration
{
    const DEFAULT_KEY_LENGTH = 11;

    /**
     * @var string default action delete used when creating foreign keys.
     */
    public $defaultOnDelete = 'CASCADE';

    /**
     * @var string default action update used when creating foreign keys.
     */
    public $defaultOnUpdate = 'CASCADE';

    /**
     * Table name used to generate the migration.
     *
     * @return string table name without prefix.
     */
    abstract public function getTableName();

    /**
     * Defines the columns which will be used on the table definition.
     *
     * @return array column_name => column_definition pairs.
     */
    abstract public function columns();

    /**
     * Table name with prefix.
     * @return string table name with the prefix.
     */
    public function getPrefixedTableName()
    {
        return '{{%' . $this->getTableName() . '}}';
    }

    /**
     * @inheritdoc
     */
    public function primaryKey($length = self::DEFAULT_KEY_LENGTH)
    {
        return $this->normalKey($length)->append('AUTO_INCREMENT PRIMARY KEY');
    }

    /**
     * Returns a key column definition. Mostly used in foreign key columns.
     *
     * @param integer $length
     * @return \yii\db\ColumnSchemaBuilder
     */
    public function normalKey($length = self::DEFAULT_KEY_LENGTH)
    {
        return $this->integer($length)->unsigned()->notNull();        
    }

    /**
     * Returns an activable column definition.
     *
     * @param boolean $default
     * @return \yii\db\ColumnSchemaBuilder
     */
    public function activable($default = true)
    {
        return $this->boolean()->notNull()->defaultValue($default);
    }

    /**
     * @inheritdoc
     */
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->prefixedTableName, array_merge(
            $this->columns(), 
            $this->defaultColumns()
        ), $tableOptions);

        $columns = $this->compositePrimaryKeys();
        if (!empty($columns)) {
            $this->addPrimaryKey(
                "{{%pk-{$this->tableName}}}",
                $this->prefixedTableName,
                $columns
            );
        }

        foreach ($this->compositeUniqueKeys() as $index => $columns) {
            $this->createIndex(
                "{{uq-{$this->tableName}-$index}}",
                $this->prefixedTableName,
                $columns,
                true // unique
            );
        }

        $this->createForeignKeys(array_merge(
            $this->foreignKeys(),
            $this->defaultForeignKeys()
        ));
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropForeignKeys(array_merge(
            $this->foreignKeys(),
            $this->defaultForeignKeys()
        ));
        $this->dropTable($this->prefixedTableName);
    }

    /**
     * Default columns for a type of table.
     *
     * @return array column_name => column_definition pairs.
     */
    public function defaultColumns()
    {
        return [];
    }

    /**
     * The default foreign keys for a type of table.
     *
     * @return array column_name => reference pairs where reference is an array
     * containing a 'table' index and optionally a 'column' index.
     */
    public function defaultForeignKeys()
    {
        return [];
    }

    /**
     * The default foreign keys for a type of table.
     *
     * @return array column_name => reference pairs where reference is an array
     * containing a 'table' index and optionally a 'column' index.
     */
    public function foreignKeys()
    {
        return [];
    }

    /**
     * Column names used to define a composite primary key.
     * Usage:
     *
     * ```php
     * public function compositePrimaryKeys()
     * {
     *     return ['sale_id', 'article_id'];
     * }
     * ```
     *
     * @return string[] column names that define the primary key. if the result
     * is empty it will be ignored.
     */
    public function compositePrimaryKeys()
    {
        return [];
    }

    /**
     * @return array index_name => index_definition pairs where index_name is
     * an string to be used to differentiate each index and index definition is
     * an array containing all the columns names for the unique index.
     */
    public function compositeUniqueKeys()
    {
        return [];
    }

    /**
     * Creates foreign keys for the table.
     *
     * @param array column_name => reference pairs where reference is an array
     * containing a 'table' index and optionally a 'column' index.
     */
    protected function createForeignKeys(array $keys)
    {
        $table = $this->getTableName();
        foreach ($keys as $columnName => $reference) {
            if (is_string($reference)) {
                $refTable = $reference;
                $refColumns = ['id'];
                $columns = [$columnName];
                $onDelete = $this->defaultOnDelete;
                $onUpdate = $this->defaultOnUpdate;
            } else {
                $refTable = $reference['table'];
                $refColumns = ArrayHelper::getValue(
                    $reference,
                    'column',
                    ['id']
                );
                $columns = ArrayHelper::getValue(
                    $reference,
                    'sourceColumns',
                    [$columnName]
                );
                
                $onDelete = ArrayHelper::getValue(
                    $reference,
                    'onDelete',
                    $this->defaultOnDelete
                );
                $onUpdate = ArrayHelper::getValue(
                    $reference,
                    'onUpdate',
                    $this->defaultOnUpdate
                );
            }

            // creates index for column
            $this->createIndex(
                "{{%idx-$table-$columnName}}",
                $this->prefixedTableName,
                $columns
            );

            // creates the foreign key
            $this->addForeignKey(
                "{{%fk-$table-$columnName}}",
                $this->prefixedTableName,
                $columns,
                "{{%$refTable}}",
                $refColumns,
                $onDelete,
                $onUpdate
            );
        }
    }

    /**
     * Drops foreign keys for the table.
     *
     * @param array column_name => reference pairs where reference is an array
     * containing a 'table' index and optionally a 'column' index.
     */
    protected function dropForeignKeys(array $keys)
    {
        $table = $this->getTableName();
        foreach ($keys as $columnName => $reference) {
            // drops the foreign key
            $this->dropForeignKey(
                "{{%fk-$table-$columnName}}",
                $this->prefixedTableName
            );

            // drops index for column
            $this->dropIndex(
                "{{%idx-$table-$columnName}}",
                $this->prefixedTableName
            );
        }
    }
}
