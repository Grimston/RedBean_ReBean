<?php

use RedBeanPHP\Facade;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Plugin;

/**
 * RedBean ReBean (Revision Bean)
 *
 * @file    ReBean.php
 * @desc    Revisionplugin to support each bean with custom revision tables and triggers
 * @author  Zewa
 *
 */
class RedBean_ReBean implements Plugin
{

    /**
     * Checks checks if a table exists in the database
     * @param $table
     * @return bool
     */
    public function tableExists($table)
    {
        return in_array($table, R::getWriter()->getTables(), false);
    }

    /**
     * Creates the revision support for the given Bean if it does not already exist.
     *
     * @param OODBBean $bean The bean-type to be revision supported
     */
    public function createRevisionSupport(OODBBean $bean)
    {
        // check if the bean already has revision support
        if ($this->tableExists('revision' . $bean->getMeta('type'))) {
            return;
        }

        $export = $bean->export();
        $duplicate = R::dispense('revision' . $bean->getMeta('type'));
        $duplicate->action = '';                                 // real enum needed
        $duplicate->original_id = $bean->id;
        $duplicate->import($export);
        $duplicate->lastedit = date('Y-m-d h:i:s');
        $duplicate->setMeta('cast.action', 'string');
        $duplicate->setMeta('cast.lastedit', 'datetime');
        Facade::store($duplicate);

        $this->createTrigger($bean, $duplicate);
    }

    private function getRevisionColumns(OODBBean $bean)
    {
        return implode(',',
            array_filter(                                              // remove nulls
                array_map(                                               // transform values instead foreach
                    static function ($val) {
                        if ($val === 'id') {
                            return 'original_id';
                        }

                        return (empty($val) || $val === null) ? null : $val;
                    },
                    array_keys($bean->getProperties())                     // use the array_key to get the colName
                )
            )
        );
    }

    private function getOriginalColumns(OODBBean $bean, $prefix)
    {
        $self = $this;
        return implode(',',
            array_filter(
                array_map(
                    static function ($col) use ($prefix) {
                        return $prefix . $col;
                    },
                    array_keys($bean->getProperties())
                )
            )
        );
    }

    private function createTrigger(OODBBean $bean, OODBBean $duplicate)
    {
        Facade::getDatabaseAdapter()->exec('DROP TRIGGER IF EXISTS `trg_' . $bean->getMeta('type') . '_AI`;');
        Facade::getDatabaseAdapter()->exec('CREATE TRIGGER `trg_' . $bean->getMeta('type') . '_AI` AFTER INSERT ON `' . $bean->getMeta('type') . "` FOR EACH ROW BEGIN
    \tINSERT INTO " . $duplicate->getMeta('type') . '(`action`, `lastedit`, ' . $this->getRevisionColumns($bean) . ") VALUES ('insert', NOW(), " . $this->getOriginalColumns($bean, 'NEW.') . ');
    END;');

        Facade::getDatabaseAdapter()->exec('DROP TRIGGER IF EXISTS `trg_' . $bean->getMeta('type') . '_AU`;');
        Facade::getDatabaseAdapter()->exec('CREATE TRIGGER `trg_' . $bean->getMeta('type') . '_AU` AFTER UPDATE ON `' . $bean->getMeta('type') . "` FOR EACH ROW BEGIN
    \tINSERT INTO " . $duplicate->getMeta('type') . '(`action`, `lastedit`, ' . $this->getRevisionColumns($bean) . ") VALUES ('update', NOW(), " . $this->getOriginalColumns($bean, 'NEW.') . ');
    END;');

        Facade::getDatabaseAdapter()->exec('DROP TRIGGER IF EXISTS `trg_' . $bean->getMeta('type') . '_AD`;');
        Facade::getDatabaseAdapter()->exec('CREATE TRIGGER `trg_' . $bean->getMeta('type') . '_AD` AFTER DELETE ON `' . $bean->getMeta('type') . "` FOR EACH ROW BEGIN
    \tINSERT INTO " . $duplicate->getMeta('type') . '(`action`, `lastedit`, ' . $this->getRevisionColumns($bean) . ") VALUES ('delete', NOW(), " . $this->getOriginalColumns($bean, 'OLD.') . ');
    END;');
    }
}

class ReBean_Exception extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

// add plugin to RedBean facade
R::ext('createRevisionSupport', static function (OODBBean $bean) {
    $rebeanPlugin = new RedBean_ReBean();
    $rebeanPlugin->createRevisionSupport($bean);
});
