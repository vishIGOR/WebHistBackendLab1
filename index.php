<?php

$method = $_SERVER['REQUEST_METHOD'];

$url = (isset($_GET['q'])) ? $_GET['q'] : '';
$url = rtrim($url, '/');
$urls = explode('/', $url);

echo json_encode($urls) . PHP_EOL;

$link = mysqli_connect("127.0.0.1:60666", "lab1_user", "lab1_pswd", "lab1");

if (!$link) {
    echo "Ошибка: Невозможно установить соединение с MySQL." . PHP_EOL;
    echo "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}

echo "Информация о сервере: " . mysqli_get_host_info($link) . PHP_EOL;
$res = $link->query("SELECT roleId FROM roles ORDER BY roleId ASC");
if (!$res) //SQL
{
    echo "Не удалось выполнить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
}
else
{
    while ($row = $res->fetch_assoc()) 
    {
        echo " id = " . $row['roleId'] . "\n";
    }
}

mysqli_close($link); //закрытие соединения, выполняется, когда мы закончили работать с БД
