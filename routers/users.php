<?php
function route($method, $urlData, $formData)
{
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/printFunctions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/dbFunctions.php');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/support/checkFunctions.php');

    $formData = (array)$formData;

    switch ($method) {
        case 'GET':
            if ($urlData === []) {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", __FUNCTION__);
                    exit;
                }

                $token = getallheaders()["Authorization"];
                if (substr($token, 0, 7) !== "Bearer ") {
                    printErrorMessage(403, "Forbidden", __FUNCTION__);
                    exit;
                }
                $token = substr($token, 7);

                $link = connectToDataBase();
                checkConnectionWithDataBase($link);

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                    exit;
                }

                $getAllUsersResult = $link->query("SELECT userId, username, roleId FROM users");

                $httpAnwserData = [];
                foreach ($getAllUsersResult->fetch_all() as $resultKey => $resultRow) {
                    $httpAnwserData[$resultKey] = (object)[
                        "userId" => $resultRow[0],
                        "username" => $resultRow[1],
                        "roleId" => $resultRow[2]
                    ];
                }
                printSuccessMessageWithData(200, "OK", $httpAnwserData);
                break;
            }

            if (preg_match('/^([1-9][0-9]*|0)$/', $urlData[0])) {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", __FUNCTION__);
                    exit;
                }

                $token = getallheaders()["Authorization"];
                if (substr($token, 0, 7) !== "Bearer ") {
                    printErrorMessage(403, "Forbidden", __FUNCTION__);
                    exit;
                }
                $token = substr($token, 7);

                $link = connectToDataBase();
                checkConnectionWithDataBase($link);

                $userIdByToken = getUserIdByToken($token, $link);
                if ($userIdByToken === null) {
                    printErrorMessage(403, "Forbidden", __FUNCTION__);
                    exit;
                }

                if ($urlData[0] !== $userIdByToken) {
                    $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                    if ($checkForAdminRole !== true) {
                        printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                        exit;
                    }
                }
                $userId = $urlData[0];

                if (!checkUserForExistingById($userId, $link)) {
                    printErrorMessage(400, "Bad Request", __FUNCTION__);
                    exit;
                }

                $httpAnwserData = getUserDataExceptPasswordById($userId, $link);
                printSuccessMessageWithData(200, "OK", $httpAnwserData);
                break;
            }

            printErrorMessage(404, "Not Found", __FUNCTION__);
            break;
        case "PATCH":

            if (!preg_match('/^([1-9][0-9]*|0)$/', $urlData[0])) {
                printErrorMessage(404, "Not Found", __FUNCTION__);
                exit;
            }

            if (count($formData) <= 0 || count($formData) > 4) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $templateForCheck = array(
                "username" => 0, "password" => 0,
                "name" => 0, "surname" => 0
            );
            if (checkDataRowsForTemplateNotMatchingOrNull($formData, $templateForCheck)) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $token = getallheaders()["Authorization"];
            if (substr($token, 0, 7) !== "Bearer ") {
                printErrorMessage(403, "Forbidden", __FUNCTION__);
                exit;
            }
            $token = substr($token, 7);

            $link = connectToDataBase();
            checkConnectionWithDataBase($link);

            $userIdByToken = getUserIdByToken($token, $link);
            if ($userIdByToken === null) {
                printErrorMessage(403, "Forbidden", __FUNCTION__);
                exit;
            }

            if ($urlData[0] !== $userIdByToken) {
                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);

                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                    exit;
                }
            }
            $userId = $urlData[0];

            if (!checkUserForExistingById($userId, $link)) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            if ($formData["username"] !== null) {
                $userIdWithThisUsername = getUserIdByUsername($formData["username"], $link);
                if ($userIdWithThisUsername !== null) {
                    if ($userIdWithThisUsername !== $userId) {
                        printErrorMessage(400, "Bad Request", __FUNCTION__);
                        exit;
                    }
                }
            }

            foreach ($formData as $rowKey => $rowData) {
                $link->query("UPDATE users SET $rowKey = '$rowData' WHERE userId = '$userId';");
            }

            $httpAnwserData = getUserDataExceptPasswordById($userId, $link);
            printSuccessMessageWithData(200, "OK", $httpAnwserData);
            break;
        case "DELETE":
            if (!preg_match('/^([1-9][0-9]*|0)$/', $urlData[0])) {
                printErrorMessage(404, "Not Found", __FUNCTION__);
                exit;
            }

            if (count($formData) !== 0) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $token = getallheaders()["Authorization"];
            if (substr($token, 0, 7) !== "Bearer ") {
                printErrorMessage(403, "Forbidden", __FUNCTION__);
                exit;
            }
            $token = substr($token, 7);

            $link = connectToDataBase();
            checkConnectionWithDataBase($link);

            $userIdByToken = getUserIdByToken($token, $link);
            if ($userIdByToken === null) {
                printErrorMessage(403, "Forbidden", __FUNCTION__);
                exit;
            }

            if ($urlData[0] !== $userIdByToken) {
                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);

                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                    exit;
                }
            }
            $userId = $urlData[0];

            if (!checkUserForExistingById($userId, $link)) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $link->query("DELETE FROM users WHERE userId ='$userId'");
            if (mysqli_affected_rows($link) !== 1) {
                printErrorMessage(500, "Internal Server Error", __FUNCTION__);
                exit;
            }
            printSuccessMessageWithoutData(200, "OK");

            break;
        case "POST":
            if (!preg_match('/^([1-9][0-9]*|0)$/', $urlData[0])) {
                printErrorMessage(404, "Not Found", __FUNCTION__);
                exit;
            }

            if (count($formData) !== 1) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $templateForCheck = array(
                "roleId" => 0
            );
            if (checkDataRowsForNull($formData, $templateForCheck)) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $token = getallheaders()["Authorization"];
            if (substr($token, 0, 7) !== "Bearer ") {
                printErrorMessage(403, "Forbidden", __FUNCTION__);
                exit;
            }
            $token = substr($token, 7);

            $link = connectToDataBase();
            checkConnectionWithDataBase($link);

            $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);

            if ($checkForAdminRole !== true) {
                printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                exit;
            }

            $userId = $urlData[0];

            if (!checkUserForExistingById($userId, $link)) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $roleId = $formData["roleId"];

            $checkRoleForExistingResult = $link->query("SELECT * FROM roles WHERE roleId = '$roleId'");

            if ($checkRoleForExistingResult->fetch_all()[0] === null) {
                printErrorMessage(400, "Bad Request", __FUNCTION__);
                exit;
            }

            $link->query("DELETE FROM roles WHERE roleId = '$roleId'");

            printSuccessMessageWithoutData(200, "OK");
            break;
        default:
            printErrorMessage(400, "Bad Request", __FUNCTION__);
            break;
    }
}

function checkUserForAdminRoleByToken($token, $link)
{
    $userId = getUserIdByToken($token, $link);
    if ($userId === null) {
        return array(403, "Forbidden", __FUNCTION__);
    }

    $checkUserRoleResult = $link->query("SELECT roleId FROM users WHERE userId ='$userId'");
    $userRole = $checkUserRoleResult->fetch_all()[0][0];
    if ($userRole === null) {
        return array(403, "Forbidden", __FUNCTION__);
    }

    $checkAdminRoleResult = $link->query("SELECT roleId FROM roles WHERE roleName = 'Администратор'");
    $adminRole = $checkAdminRoleResult->fetch_all()[0][0];
    if ($adminRole === null) {
        return array(500, "Internal Server Error", __FUNCTION__);
    }

    if ($adminRole !== $userRole) {
        return array(403, "Forbidden", __FUNCTION__);
    }
    return  true;
}

function getUserIdByToken($token, $link)
{
    $checkTokenResult = $link->query("SELECT userId FROM authorizationtokens WHERE tokenValue='$token'");

    return $checkTokenResult->fetch_all()[0][0];
}

function getUserDataExceptPasswordById($userId, $link)
{
    $getUserByIdResult = $link->query("SELECT userId, username, roleId, name, surname FROM users WHERE userId='$userId'");

    $resultRow = $getUserByIdResult->fetch_all()[0];
    return (object)[
        "userId" => $resultRow[0],
        "username" => $resultRow[1],
        "roleId" => $resultRow[2],
        "name" => $resultRow[3],
        "surname" => $resultRow[4]
    ];
}

function getUserIdByUsername($username, $link)
{
    $getUserIdByUsernameResult = $link->query("SELECT userId FROM users WHERE username = '$username'");

    return $getUserIdByUsernameResult->fetch_all()[0][0];
}

function checkUserForExistingById($userId, $link)
{
    $link->query("SELECT username FROM users WHERE userId = '$userId'");

    if (mysqli_affected_rows($link) === 0)
        return false;
    return true;
}
