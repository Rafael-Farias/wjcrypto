<?php

namespace WjCrypto\Models\Database;

use PDO;
use PDOException;

class Database
{
    protected static function getConnection(): PDO
    {
        static $conn = null;

        if ($conn === null) {
            try {
                $dsn = 'mysql:host=localhost' . ';dbname=wjcrypto' . ';charset=utf8';
                $conn = new PDO($dsn, 'root', 'qwert12345!');
            } catch (PDOException $exception) {
                echo 'An error occurred while trying to connect to the database: ' . $exception->getMessage();
            }
        }
        return $conn;
    }
}