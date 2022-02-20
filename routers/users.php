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
                    printErrorMessage(400, "Bad Request", __FUNCTION__);
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
                    printErrorMessage(400, "Bad Request", __FUNCTION__);
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

                $getUserByIdResult = $link->query("SELECT userId, username, roleId, name, surname FROM users WHERE userId='$userId'");

                $resultRow = $getUserByIdResult->fetch_all()[0];
                $httpAnwserData = (object)[
                    "userId" => $resultRow[0],
                    "username" => $resultRow[1],
                    "roleId" => $resultRow[2],
                    "name" => $resultRow[3],
                    "surname" => $resultRow[4]
                ];
                printSuccessMessageWithData(200, "OK", $httpAnwserData);
                break;
            }

            printErrorMessage(404, "Not Found", __FUNCTION__);
            break;
        case "PATCH":

            printErrorMessage(404, "Not Found", __FUNCTION__);
            break;
        case "DELETE":

            printErrorMessage(404, "Not Found", __FUNCTION__);
            break;
        case "POST":

            printErrorMessage(404, "Not Found", __FUNCTION__);
            break;
        default:
            printErrorMessage(404, "Not Found", __FUNCTION__);
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
        if ($userRole === null) {
            return array(500, "Internal Server Error", __FUNCTION__);
        }
    }
    return true;
}

function getUserIdByToken($token, $link)
{
    $checkTokenResult = $link->query("SELECT userId FROM authorizationtokens WHERE tokenValue='$token'");

    return $checkTokenResult->fetch_all()[0][0];
}
