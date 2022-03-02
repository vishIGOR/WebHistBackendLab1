<?php
function route($method, $urlData, $formData)
{
    $formData = (array)$formData;

    if ($method !== 'POST') {
        printErrorMessage(400, "Bad Request", "type of request");
    }

    if (count($formData) !== 0) {
        printErrorMessage(400, "Bad Request", "number of rows");
    }

    $token = getallheaders()["Authorization"];
    if (substr($token, 0, 7) !== "Bearer "){
        printErrorMessage(403, "Forbidden", "token");
    }
    $token = substr($token, 7);

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    $link->query("DELETE FROM authorizationtokens WHERE tokenValue = '$token'");
    if(mysqli_affected_rows($link) !== 1){
        printErrorMessage(403, "Forbidden", "token");
    }
    printSuccessMessageWithoutData(200,"OK");
}
