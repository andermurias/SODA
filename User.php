<?php

require_once 'Soda.php';

class User extends andermurias\Soda {
    public $id = self::TYPE_PRIMARY_KEY;
    public $username = self::TYPE_STRING;
    public $password = self::TYPE_STRING;
    public $name = self::TYPE_STRING;
    public $createdAt = self::TYPE_DATETIME;
    public $updatedAt = self::TYPE_DATETIME;
}