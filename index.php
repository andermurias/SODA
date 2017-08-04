<?php

require_once 'User.php';

$u = User::find(['id' => 1]);
var_dump($u);
