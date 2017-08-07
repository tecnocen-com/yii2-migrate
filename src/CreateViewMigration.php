<?php

namespace tecnocen\migrate;

/**
 * Handles the creation for one view query.
 *
 * @author Angel (Faryshta) Guevara <aguevara@alquimiadigital.mx>
 */
abstract class CreateViewMigration extends \yii\db\Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->execute(
            'CREATE VIEW '
            . $this->quotedViewName()
            . ' AS '
            . $this->viewQuery()->createCommand($this->getDb())->getRawSql()
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->execute('DROP VIEW ' . $this->quotedViewName());
    }

    /**
     * @return string view name quoted and with prefixes.
     */
    private function quotedViewName()
    {
        return $this->getDb()->quoteTableName('{{%' . $this->viewName() . '}}');
    }
    
    /**
     * @return string the name of the view to be created. It will be
     * automatically quoted.
     */
    abstract public function viewName();
    
    /**
     * @return \yii\db\Query query to be used to obtain the SQL to create the
     * view.
     */
    abstract public function viewQuery();
}
