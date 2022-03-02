<?php
function route($method, $urlData, $formData)
{
    $formData = (array)$formData;

    if ($method !== 'POST') {
        printErrorMessage(400, "Bad Request", "type of request");
    }

    if (count($formData) != 2) {
        printErrorMessage(400, "Bad Request","number of rows");
    }

    $templateForCheck = array("username" => 0, "password" => 0);
    if (checkDataRowsForNull($formData, $templateForCheck)) {
        printErrorMessage(400, "Bad Request", "datarows");
    }

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    $username = $formData["username"];
    $password = hash("sha1", $formData["password"]) ;

    $checkForUser = $link->query("SELECT * FROM users WHERE username = '$username' AND password = '$password'");
    $checkForUser = $checkForUser->fetch_all()[0];

    if ($checkForUser === null) {
        printErrorMessage(403, "Forbidden", "data");
    }

    $userId = $checkForUser[0];
    $link->query("DELETE FROM authorizationtokens where userId = '$userId'");

    $token = bin2hex(random_bytes(20));
    $checkForSameTokenResult = null;
    while (true) {
        $checkForSameTokenResult = $link->query("SELECT * FROM authorizationtokens WHERE tokenValue='$token'");

        if ($checkForSameTokenResult->fetch_all()[0] === null)
            break;
        $token = bin2hex(random_bytes(20));
    }

    $link->query("INSERT authorizationtokens(userId,tokenValue) 
    VALUES('$userId', '$token')");

    printSuccessMessageWithData(200, "OK", ["token" => $token]);
}
