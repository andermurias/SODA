<?php

require_once 'User.php';

/** @var $u User */
//$u = User::find(['id' => 1]);
//$u->update();

$u = new User();

$u->create();

/*
$u->name = "Ander";
$u->username = "andermurias";
$u->password = "123456";
$u->createdAt = date('Y-m-d H:i:s');
$u->updatedAt = date('Y-m-d H:i:s');

//$u->save();

var_dump($u);

*/
