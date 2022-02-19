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

    if (count($formData) != 2) {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }

    $templateForCheck = array("username" => 0, "password" => 0);
    if (checkDataRowsForNull($formData, $templateForCheck)) {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    $username = $formData["username"];
    $password = $formData["password"];

    $checkForUser = $link->query("SELECT * FROM users WHERE username = '$username' AND password = '$password'");
    $checkForUser = $checkForUser->fetch_all()[0];

    if ($checkForUser === null) {
        printErrorMessage(403, "Forbidden", __FUNCTION__);
        exit;
    }

    $userId = $checkForUser[0];
    $link->query("DELETE FROM authorizationtokens where userId = '$userId'");

    $token = bin2hex(random_bytes(20));
    $checkForSameToken;
    while (true) {
        $checkForSameToken = $link->query("SELECT * FROM authorizationtokens WHERE tokenValue='$token'");

        if ($checkForSameToken->fetch_all()[0] === null)
            break;
        $token = bin2hex(random_bytes(20));
    }

    $putTokenIntoDatabase = $link->query("INSERT authorizationtokens(userId,tokenValue) 
    VALUES('$userId', '$token')");

    printSuccessMessageWithData(200, "OK", ["token" => $token]);
}
