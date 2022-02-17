<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/support/printFunctions.php');

$method = $_SERVER['REQUEST_METHOD'];

function getFormData($method)
{
    if ($method === 'GET') return $_GET;
    if ($method === 'POST' && !empty($_POST)) return $_POST;

    $incomingData = file_get_contents('php://input');
    $decodedJSON = json_decode($incomingData);
    if ($decodedJSON) {
        $data = $decodedJSON;
    } else {
        $data = array();
        $exploded = explode('&', file_get_contents('php://input'));
        foreach ($exploded as $pair) {
            $item = explode('=', $pair);
            if (count($item) == 2) {
                $data[urldecode($item[0])] = urldecode($item[1]);
            }
        }
    }
    return $data;
}

$formData = getFormData($method);

$url = (isset($_GET['q'])) ? $_GET['q'] : '';
$url = rtrim($url, '/');
$urls = explode('/', $url);

echo json_encode($formData) . PHP_EOL;
echo json_encode($urls) . PHP_EOL;

$router = $urls[0];
$urlData = array_slice($urls, 1);

try {
    if (!file_exists('routers/' . $router . '.php')) {
        throw new Exception("File not found");
    }
} catch (Exception $e) {
    printErrorMessage(404, "Not Found", "mainFunction");
    exit;
}

include_once 'routers/' . $router . '.php';
route($method, $urlData, $formData);

//  echo bin2hex(random_bytes(20)) . PHP_EOL; //генерация токена

//echo json_encode(getallheaders()) . PHP_EOL; //получение headers

// $link = mysqli_connect("127.0.0.1:60666", "lab1_user", "lab1_pswd", "lab1");

// if (!$link) {
//     echo "Ошибка: Невозможно установить соединение с MySQL." . PHP_EOL;
//     echo "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL;
//     echo "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL;
//     exit;
// }

// echo "Информация о сервере: " . mysqli_get_host_info($link) . PHP_EOL;
// $res = $link->query("SELECT roleId FROM roles ORDER BY roleId ASC");
// if (!$res) //SQL
// {
//     echo "Не удалось выполнить запрос: (" . $mysqli->errno . ") " . $mysqli->error;
// }
// else
// {
//     while ($row = $res->fetch_assoc()) 
//     {
//         echo " id = " . $row['roleId'] . "\n";
//     }
// }

// mysqli_close($link); 
