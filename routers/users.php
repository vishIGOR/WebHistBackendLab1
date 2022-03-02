<?php
function route($method, $urlData, $formData)
{

    $formData = (array)$formData;

    switch ($method) {
        case 'GET':
            if ($urlData === []) {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", "number of rows");
                }

                $token = getallheaders()["Authorization"];
                if (substr($token, 0, 7) !== "Bearer ") {
                    printErrorMessage(403, "Forbidden", "token");
                }
                $token = substr($token, 7);

                $link = connectToDataBase();
                checkConnectionWithDataBase($link);

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
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
                    printErrorMessage(400, "Bad Request", "number of rows");
                }

                $token = getallheaders()["Authorization"];
                if (substr($token, 0, 7) !== "Bearer ") {
                    printErrorMessage(403, "Forbidden", "token");
                }
                $token = substr($token, 7);

                $link = connectToDataBase();
                checkConnectionWithDataBase($link);

                $userIdByToken = getUserIdByToken($token, $link);
                if ($userIdByToken === null) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                if ($urlData[0] !== $userIdByToken) {
                    $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                    if ($checkForAdminRole !== true) {
                        printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                    }
                }
                $userId = $urlData[0];

                if (!checkUserForExistingById($userId, $link)) {
                    printErrorMessage(400, "Bad Request", "userId");
                }

                $httpAnwserData = getUserDataExceptPasswordById($userId, $link);
                printSuccessMessageWithData(200, "OK", $httpAnwserData);
                break;
            }

            printErrorMessage(404, "Not Found", "userId");
            break;
        case "PATCH":

            if (!preg_match('/^([1-9][0-9]*|0)$/', $urlData[0])) {
                printErrorMessage(404, "Not Found", "userId");
            }

            if (count($formData) <= 0 || count($formData) > 4) {
                printErrorMessage(400, "Bad Request", "number of rows");
            }

            $templateForCheck = array(
                "username" => 0, "password" => 0,
                "name" => 0, "surname" => 0
            );
            if (checkDataRowsForTemplateNotMatchingOrNull($formData, $templateForCheck)) {
                printErrorMessage(400, "Bad Request", "datarows");
            }

            $token = getallheaders()["Authorization"];
            if (substr($token, 0, 7) !== "Bearer ") {
                printErrorMessage(403, "Forbidden", "token");
            }
            $token = substr($token, 7);

            $link = connectToDataBase();
            checkConnectionWithDataBase($link);

            $userIdByToken = getUserIdByToken($token, $link);
            if ($userIdByToken === null) {
                printErrorMessage(403, "Forbidden", "token");
            }

            if ($urlData[0] !== $userIdByToken) {
                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);

                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }
            }
            $userId = $urlData[0];

            if (!checkUserForExistingById($userId, $link)) {
                printErrorMessage(400, "Bad Request", "userId");
            }

            if ($formData["username"] !== null) {
                $userIdWithThisUsername = getUserIdByUsername($formData["username"], $link);
                if ($userIdWithThisUsername !== null) {
                    if ($userIdWithThisUsername !== $userId) {
                        printErrorMessage(400, "Bad Request", "access");
                    }
                }
            }

            if ($formData["password"] !== null) {
                $formData["password"] = hash("sha1",$formData["password"]);
            }

            foreach ($formData as $rowKey => $rowData) {
                $link->query("UPDATE users SET $rowKey = '$rowData' WHERE userId = '$userId';");
            }

            $httpAnwserData = getUserDataExceptPasswordById($userId, $link);
            printSuccessMessageWithData(200, "OK", $httpAnwserData);
            break;
        case "DELETE":
            if (!preg_match('/^([1-9][0-9]*|0)$/', $urlData[0])) {
                printErrorMessage(404, "Not Found", "userId");
            }

            if (count($formData) !== 0) {
                printErrorMessage(400, "Bad Request", "number of rows");
            }

            $token = getallheaders()["Authorization"];
            if (substr($token, 0, 7) !== "Bearer ") {
                printErrorMessage(403, "Forbidden", "token");
            }
            $token = substr($token, 7);

            $link = connectToDataBase();
            checkConnectionWithDataBase($link);

            $userIdByToken = getUserIdByToken($token, $link);
            if ($userIdByToken === null) {
                printErrorMessage(403, "Forbidden", "token");
            }

            if ($urlData[0] !== $userIdByToken) {
                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);

                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }
            }
            $userId = $urlData[0];

            if (!checkUserForExistingById($userId, $link)) {
                printErrorMessage(400, "Bad Request", "userId");
            }

            $link->query("DELETE FROM users WHERE userId ='$userId'");
            if (mysqli_affected_rows($link) !== 1) {
                printErrorMessage(500, "Internal Server Error", "server");
            }
            printSuccessMessageWithoutData(200, "OK");

            break;
        case "POST":
            if (!preg_match('/^([1-9][0-9]*|0)$/', $urlData[0]) && $urlData[1] === "role") {
                printErrorMessage(404, "Not Found", "userId");
            }

            if (count($formData) !== 1) {
                printErrorMessage(400, "Bad Request", "number of rows");
            }

            $templateForCheck = array(
                "roleId" => 0
            );
            if (checkDataRowsForNull($formData, $templateForCheck)) {
                printErrorMessage(400, "Bad Request", "datarows");
            }

            $token = getallheaders()["Authorization"];
            if (substr($token, 0, 7) !== "Bearer ") {
                printErrorMessage(403, "Forbidden", "token");
            }
            $token = substr($token, 7);

            $link = connectToDataBase();
            checkConnectionWithDataBase($link);

            $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);

            if ($checkForAdminRole !== true) {
                printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
            }

            $userId = $urlData[0];

            if (!checkUserForExistingById($userId, $link)) {
                printErrorMessage(400, "Bad Request", "userId");
            }

            $roleId = $formData["roleId"];

            $checkRoleForExistingResult = $link->query("SELECT * FROM roles WHERE roleId = '$roleId'");

            if ($checkRoleForExistingResult->fetch_all()[0] === null) {
                printErrorMessage(400, "Bad Request", "roleId");
            }

            $link->query("UPDATE users SET roleId = '$roleId' WHERE userId = '$userId'");

            printSuccessMessageWithoutData(200, "OK");
            break;
        default:
            printErrorMessage(400, "Bad Request", "type of request");
            break;
    }
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
