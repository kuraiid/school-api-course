<?php
/**
 * Функция возвращает подключение к БД или завершает выполнение при ошибке
 */
function getDBConnection(): mysqli
{
    $host = 'MySQL-8.0';
    $user = 'root';
    $pass = '';
    $dbname = 'school_db';

    $mysqli = new mysqli($host, $user, $pass, $dbname);

    if ($mysqli->connect_errno) {
        require_once './controller/BaseController.php';

        $controller = new BaseController();
        $controller->sendServerError("Ошибка подключения к БД: " . $mysqli->connect_error);
        exit();
    }

    return $mysqli;
}