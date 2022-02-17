<?php
function route($method, $urlData, $formData)
{
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/printFunctions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/dbFunctions.php');

    if ($method !== 'POST') {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }

    foreach ($formData as $dataRow) {
        if ($dataRow === '') {
            printErrorMessage(400, "Bad Request", __FUNCTION__);
            exit;
        }
    }

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    // $usernameCheck = $link->prepare("SELECT * FROM users WHERE username = ?");
    // $usernameCheck->bind_param("s", $username);
    // $usernameCheck->execute();

    $username = $formData->username;
    $usernameCheck = $link->query("SELECT * FROM users WHERE username = '$username'");

    if ($usernameCheck->fetch_all() != []) {
        printErrorMessage(400,"Bad Request",__FUNCTION__);
        exit;
    }

    // если дошло сюда - все данные правильны, но я не сделал логин
}
