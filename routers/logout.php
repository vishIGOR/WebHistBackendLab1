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

    if (count($formData) !== 0) {
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }

    $token = getallheaders()["Authorization"];
    if (substr($token, 0, 7) !== "Bearer "){
        printErrorMessage(400, "Bad Request", __FUNCTION__);
        exit;
    }
    $token = substr($token, 7);

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    $link->query("DELETE FROM authorizationtokens WHERE tokenValue = '$token'");
    if(mysqli_affected_rows($link) !== 1){
        printErrorMessage(403, "Forbidden", __FUNCTION__);
        exit;
    }
    printSuccessMessageWithoutData(200,"OK");
}
