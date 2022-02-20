<?php
function route($method, $urlData, $formData)
{
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/printFunctions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/dbFunctions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/checkFunctions.php');

    $formData = (array)$formData;

    if ($method !== 'POST') {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }

    if (count($formData) !== 4) {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }

    $templateForCheck = array(
        "username" => 0, "password" => 0,
        "name" => 0, "surname" => 0
    );
    if (checkDataRowsForNull($formData, $templateForCheck)) {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }


    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    //? неудачная попытка запроса с подготовкой
    // $usernameCheck = $link->prepare("SELECT * FROM users WHERE username = ?");
    // $usernameCheck->bind_param("s", $username);
    // $usernameCheck->execute();

    $username = $formData["username"];
    $usernameCheckResult = $link->query("SELECT * FROM users WHERE username = '$username'");

    if ($usernameCheckResult->fetch_all()[0] != null) {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }

    $name = $formData["name"];
    $surname = $formData["surname"];
    $password = $formData["password"];
    $link->query("INSERT users(username, name, surname, password) 
    VALUES ('$username','$name','$surname','$password')");

    $checkForUserResult = $link->query("SELECT * FROM users WHERE username = '$username' AND password = '$password'");
    $checkForUserResult = $checkForUserResult->fetch_all()[0];
    $userId = $checkForUserResult[0];

    $token = bin2hex(random_bytes(20));
    $link->query("INSERT authorizationtokens(userId,tokenValue) 
    VALUES('$userId', '$token')");
    
    printSuccessMessageWithData(200, "OK", ["token" => $token]);
}
