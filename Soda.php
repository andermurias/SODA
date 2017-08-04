<?php

namespace andermurias;

require_once 'SodaConfig.php';

class Soda
{
    /*
     * TYPE CONSTS
     */
    const AUTO_INCREMENT = 'AUTO_INCREMENT';
    const TYPE_PRIMARY_KEY = 'INT ' . self::AUTO_INCREMENT . ' PRIMARY KEY';
    const TYPE_STRING = 'VARCHAR(255)';
    const TYPE_TEXT = 'TEXT(65.535)';
    const TYPE_INTEGER = 'INT';
    const TYPE_FLOAT = 'FLOAT';
    const TYPE_BOOLEAN = 'BOOL';
    const TYPE_DATETIME = 'DATETIME';
    /*
     * END TYPE CONSTS
     */

    /** @var \mysqli */
    private $db = null;
    /** @var array */
    private $vars;
    /** @var  $previousVars */
    private $previousVars;
    /** @var string */
    private $table;
    /** @var bool */
    private $showErrors;
    /** @var  boolean */
    private $objectExists = false;

    /** @var string */
    protected $index = ['id' => self::TYPE_PRIMARY_KEY];

    function __construct()
    {
        $this->showErrors = property_exists(new SodaConfig, 'showErrors') ? SodaConfig::$showErrors : false;

        $this->vars = $this->getVars();
        var_dump($this->vars);
        $this->table = SodaConfig::$tablePrefix . strtolower(get_class($this));
        $this->init();
    }

    protected function init() {

    }
    /*
     * Pre & Post execute functions
     */

    /**
     * Funtion that is executed at the begining of all query functions
     */
    final private function onInit()
    {
        $this->connect();
    }

    /**
     * Funtion that is executed at the end of all query functions
     */
    final private function onFinish()
    {
        $this->disconnect();
    }

    /*
     *  CONNECTION PART
     */

    /**
     * Connect function that starts the database connection, it's ended in the *disconnect()* method
     */
    final private function connect()
    {
        $this->db = new \mysqli(SodaConfig::$hostname, SodaConfig::$username, SodaConfig::$password, SodaConfig::$database);
        if ($this->db == null || $this->db->connect_errno > 0) {
            die('Unable to connect to database [' . $this->db->connect_error . ']');
        }
    }

    /**
     * Disconnects the database, it's started in *connect()*
     */
    final private function disconnect()
    {
        $this->db->close();
        $this->db = null;
    }

    /*
     * END OF CONNECTION PART
     */

    /*
     * HELPER FUNCTIONS
     */

    final private function getVars()
    {
        return array_merge($this->index, array_diff_key(get_object_vars($this), get_class_vars(get_parent_class($this))));
    }

    public final function getIndex()
    {
        return $this->index;
    }

    final private function showError(\Exception $exception)
    {
        if ($this->showErrors) {
            echo $exception->getTraceAsString();
        }
    }

    final private function getTableStructure()
    {
        $values = $this->vars;
        return array_reduce(array_keys($values), function ($return, $value) use ($values) {
            return $return . (strlen($return) ? ', ' : '') . "`$value` {$values[$value]}";
        });
    }

    final private function getWhereValues($values)
    {
        return array_reduce(array_keys($values), function ($return, $value) use ($values) {
            return $return . (strlen($return) ? ' AND ' : '') . "`$value` = {$values[$value]}";
        });
    }

    final private function getTableFields()
    {
        $return = '';
        array_walk($this->vars, function ($value, $key) use (&$return) {
            if (!strpos($value, self::AUTO_INCREMENT))
                $return .= (strlen($return) ? ', ' : '') . "`$key`";
        });
        return $return;
    }

    final private function getTableVars()
    {
        $vars = $this->getVars();
        $return = '';
        array_walk($vars, function ($value, $key) use (&$return) {
            if (!strpos($this->vars[$key], self::AUTO_INCREMENT))
                $return .= (strlen($return) ? ', ' : '') . "'$value'";
        });
        return $return;
    }

    final private function prepareInsertObject()
    {
        $tableStructure = $this->getTableFields();
        $newValues = $this->getTableVars();
        return "($tableStructure) VALUES ($newValues)";
    }

    final private function prepareUpdateObject()
    {
        $vars = $this->getVars();
        $return = '';
        array_walk($vars, function ($value, $key) use (&$return) {
            if (!strpos($this->vars[$key], self::AUTO_INCREMENT))
                $return .= (strlen($return) ? ', ' : '') . " `$key` = '$value'";
        });

        $return .= ' WHERE ';

        $prevVars = $this->previousVars;
        array_walk($prevVars, function ($value, $key) use (&$return) {
            if (!strpos($this->vars[$key], self::AUTO_INCREMENT))
                $return .= (strlen($return) ? ' AND ' : '') . " `$key` = '$value'";
        });
        return $return;
    }

    /*
     * END OF HELPER FUNCTIONS
     */

    final function execute($sql, $isSelect = false)
    {
        try {
            $this->onInit();
            $query = $this->db->query($sql);
            $insertId = $this->db->insert_id;
            $this->onFinish();
            if ($isSelect) {
                return $query;
            } else {
                return $insertId;
            }
        } catch (\Exception $exception) {
            $this->showError($exception);
            return false;
        }
    }

    final function executeSelect($sql)
    {
        return $this->execute($sql, true)->fetch_assoc();
    }


    final function create()
    {
        $tableStructure = $this->getTableStructure();
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` ($tableStructure)";
        var_dump($sql);
        $this->execute($sql);
    }

    final function save()
    {
        if ($this->objectExists) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    final static function find($where)
    {
        $instance = new static();
        return $instance->select($where);
    }

    final function select($where)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE {$this->getWhereValues($where)}";
        echo $sql;
        $result = $this->executeSelect($sql);
        array_walk($result, function ($value, $key) {
            if (array_key_exists($key, $this->index)) {
                $this->index[$key] = $value;
            } else {
                $this->{$key} = $value;
            }
        });
        $this->objectExists = true;
        $this->previousVars = $this->getVars();
        return $this;
    }

    final function update()
    {
        $tableUpdate = $this->prepareUpdateObject();
        $sql = "UPDATE `{$this->table}` SET $tableUpdate";
        //$this->execute($sql);
        var_dump($sql);
    }

    final function insert()
    {
        $tableInsert = $this->prepareInsertObject();
        $sql = "INSERT INTO `{$this->table}` $tableInsert";
        $this->execute($sql);
    }

    final function check()
    {
        return $this->objectExists;
    }

    final function delete()
    {

    }
}