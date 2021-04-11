<?php
function db_connect()
{

  // setting ************************
  $db_user = 'manabu';
  $db_pass = '726099';

  $db_host = 'localhost';
  $db_name = 'sampledb';
  $db_char = 'utf8';
  $db_type = 'mysql'; // MySQL

  //*********************************

  // MySQL
  $dsn = "$db_type:host=$db_host;dbname=$db_name;charset=$db_char";

  // connect
  try {
    $pdo = new PDO($dsn, $db_user, $db_pass);

    // default setting
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

  } catch(PDOException $Exception) {
    die('エラー:'.$Exception->getMessage());
  }

  return $pdo;
}
?>
