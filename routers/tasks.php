<?php

function route($method, $urlData, $formData)
{
    $formData = (array)$formData;

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    $queryResult = null;
    $httpAnwserData = [];

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads";
    switch ($method) {
        case "GET":
            if ($urlData === []) {
                if (count($formData) <= 1 || count($formData) > 3) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $filterName = $formData["name"];
                $filterTopicId = $formData["topic"];

                if ($filterTopicId === null) {
                    $queryResult = $link->query("SELECT id, name, topicId FROM tasks");
                } else {
                    if (!checkStringForNumber($filterTopicId)) {
                        printErrorMessage("400", "Bad Request", "topicId");
                    }
                    $queryResult = $link->query("SELECT id, name, topicId FROM tasks WHERE topicId = '$filterTopicId'");
                }


                if ($filterName === null) {
                    foreach ($queryResult->fetch_all() as $resultKey => $resultRow) {
                        $httpAnwserData[$resultKey] = (object)[
                            "id" => $resultRow[0],
                            "name" => $resultRow[1],
                            "topicId" => $resultRow[2],
                        ];
                    }
                } else {
                    foreach ($queryResult->fetch_all() as $resultKey => $resultRow) {
                        if (preg_match("/" . $filterName . "/", $resultRow[1])) {
                            $httpAnwserData[$resultKey] = (object)[
                                "id" => $resultRow[0],
                                "name" => $resultRow[1],
                                "topicId" => $resultRow[2],
                            ];
                        }
                    }
                }

                printSuccessMessageWithData(200, "OK", $httpAnwserData);
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === null) {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                if (!checkUserForExistingByToken($token, $link)) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $taskId = $urlData[0];

                if (!checkTopicForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                printTaskById($taskId, $link);
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "input") {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $taskId = $urlData[0];
                $pathToGetFile = $uploadDir . "/id" . $taskId;

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                if (!checkUserForExistingByToken($token, $link)) {
                    printErrorMessage("400", "Bad Request", "token");
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                if (!file_exists($pathToGetFile . "/input.txt")) {
                    printErrorMessage("400", "Bad Request", "file(not found)");
                }

                printSuccessMessageWithFile(200, "OK", $pathToGetFile . "/input.txt");
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "output") {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $taskId = $urlData[0];
                $pathToGetFile = $uploadDir . "/id" . $taskId;

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                if (!checkUserForExistingByToken($token, $link)) {
                    printErrorMessage("400", "Bad Request", "token");
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                if (!file_exists($pathToGetFile . "/output.txt")) {
                    printErrorMessage("400", "Bad Request", "file(not found)");
                }

                printSuccessMessageWithFile(200, "OK", $pathToGetFile . "/output.txt");
            }

            printErrorMessage("400", "Bad Request", "request");
            break;
        case "POST":
            if ($urlData === []) {
                if (count($formData) !== 4) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $templateForCheck = array(
                    "name" => 0,
                    "topicId" => 0,
                    "description" => 0,
                    "price" => 0
                );
                if (checkDataRowsForNull($formData, $templateForCheck)) {
                    printErrorMessage(400, "Bad Request", "datarows");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                $newName = $formData["name"];
                $newTopicId = $formData["topicId"];
                $newDescription = $formData["description"];
                $newPrice = $formData["price"];

                if (!checkStringForNumber($newTopicId) || !checkStringForNumber($newPrice)) {
                    printErrorMessage("400", "Bad Request", "data");
                }

                if (!checkTopicForExistingById($newTopicId, $link)) {
                    printErrorMessage("400", "Bad Request", "topicId");
                }

                $link->query("INSERT tasks(name, topicId, description, price) VALUES ('$newName','$newTopicId','$newDescription','$newPrice')");

                if (mysqli_affected_rows($link) === 0) {
                    printErrorMessage("400", "Bad Request", "data");
                }

                printTaskByName($newName, $link);
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "input") {
                $taskId = $urlData[0];
                $pathToPutFile = $uploadDir . "/id" . $taskId;

                $file = $_FILES['input'];
                if ($file === null) {
                    printErrorMessage(400, "Bad Request", "file(not found)");
                }

                if ($file['type'] !== "text/plain") {
                    printErrorMessage(400, "Bad Request", "type of file");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                if (!is_dir($pathToPutFile)) {
                    mkdir($pathToPutFile);
                }

                if (file_exists($pathToPutFile . "/input.txt")) {
                    unlink($pathToPutFile . "/input.txt");
                }

                move_uploaded_file($file["tmp_name"], $pathToPutFile . "/input.txt");

                printSuccessMessageWithoutData(200, "OK");
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "output") {
                $taskId = $urlData[0];
                $pathToPutFile = $uploadDir . "/id" . $taskId;

                $file = $_FILES['input'];
                if ($file === null) {
                    printErrorMessage(400, "Bad Request", "file(not found)");
                }

                if ($file['type'] !== "text/plain") {
                    printErrorMessage(400, "Bad Request", "type of file");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                if (!is_dir($pathToPutFile)) {
                    mkdir($pathToPutFile);
                }

                if (file_exists($pathToPutFile . "/output.txt")) {
                    unlink($pathToPutFile . "/output.txt");
                }

                move_uploaded_file($file["tmp_name"], $pathToPutFile . "/output.txt");

                printSuccessMessageWithoutData(200, "OK");
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "solution") {
                $taskId = $urlData[0];

                if (count($formData) !== 2) {
                    printErrorMessage(400, "Bad Request", "number of data rows");
                }

                $templateForCheck = array(
                    "sourceCode" => 0,
                    "programmingLanguage" => 0
                );
                if (checkDataRowsForNull($formData, $templateForCheck)) {
                    printErrorMessage(400, "Bad Request", "datarows");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                if (!checkUserForExistingByToken($token, $link)) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                $userId = getUserIdByToken($token, $link);
                $sourceCode = $formData["sourceCode"];
                $programmingLanguage = $formData["programmingLanguage"];

                $link->query("DELETE FROM solutions WHERE authorId = '$userId' AND taskId = '$taskId'");
                $link->query("INSERT INTO solutions(sourceCode, programmingLanguage, authorId, taskId) VALUES ('$sourceCode','$programmingLanguage','$userId','$taskId')");

                if (mysqli_affected_rows($link) === 0) {
                    printErrorMessage("400", "Bad Request", "data");
                }

                printSolutionByAuthorIdAndTaskId($userId, $taskId, $link);
            }

            printErrorMessage("400", "Bad Request", "request");
            break;
        case "PATCH":
            if (checkStringForNumber($urlData[0]) && $urlData[1] === null) {
                $taskId = $urlData[0];

                if (count($formData) < 1 || count($formData) > 4) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $templateForCheck = array(
                    "name" => 0,
                    "topicId" => 0,
                    "description" => 0,
                    "price" => 0
                );
                if (checkDataRowsForTemplateNotMatchingOrNull($formData, $templateForCheck)) {
                    printErrorMessage(400, "Bad Request", "datarows");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                $newName = $formData["name"];
                $newTopicId = $formData["topicId"];
                $newDescription = $formData["description"];
                $newPrice = $formData["price"];

                if ($newName !== null) {
                    $link->query("UPDATE tasks SET name='$newName' WHERE id='$taskId'");
                }
                if ($newTopicId !== null && checkStringForNumber($newTopicId)) {
                    $link->query("UPDATE tasks SET topicId='$newTopicId' WHERE id='$taskId'");
                }
                if ($newDescription !== null) {
                    $link->query("UPDATE tasks SET description='$newDescription' WHERE id='$taskId'");
                }
                if ($newPrice !== null && checkStringForNumber($newPrice)) {
                    $link->query("UPDATE tasks SET price='$newPrice' WHERE id='$taskId'");
                }

                printTaskById($taskId, $link);
            }
            printErrorMessage("400", "Bad Request", "request");
            break;
        case "DELETE":
            if (checkStringForNumber($urlData[0]) && $urlData[1] === null) {
                if (count($formData) !== 0) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                $taskId = $urlData[0];
                $pathToDeleteFile = $uploadDir . "/id" . $taskId;

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }

                $link->query("DELETE FROM tasks WHERE id='$taskId'");

                if (mysqli_affected_rows($link) === 0) {
                    printErrorMessage("500", "Internal Server Error", "deleting");
                }

                if (is_dir($pathToDeleteFile)) {
                    if (file_exists($pathToDeleteFile . "/input.txt")) {
                        unlink($pathToDeleteFile . "/input.txt");
                    }
                    if (file_exists($pathToDeleteFile . "/output.txt")) {
                        unlink($pathToDeleteFile . "/output.txt");
                    }
                    rmdir($pathToDeleteFile);
                }
                printSuccessMessageWithoutData(200, "OK");
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "input") {
                $taskId = $urlData[0];
                $pathToDeleteFile = $uploadDir . "/id" . $taskId;

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }


                if (!file_exists($pathToDeleteFile . "/input.txt")) {
                    printErrorMessage("400", "Bad Request", "file(not found)");
                }

                unlink($pathToDeleteFile . "/input.txt");

                printSuccessMessageWithoutData(200, "OK");
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "output") {
                $taskId = $urlData[0];
                $pathToDeleteFile = $uploadDir . "/id" . $taskId;

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                if (!checkTaskForExistingById($taskId, $link)) {
                    printErrorMessage("400", "Bad Request", "taskId");
                }


                if (!file_exists($pathToDeleteFile . "/output.txt")) {
                    printErrorMessage("400", "Bad Request", "file(not found)");
                }

                unlink($pathToDeleteFile . "/output.txt");

                printSuccessMessageWithoutData(200, "OK");
            }

            printErrorMessage("400", "Bad Request", "request");
            break;
        default:
            printErrorMessage("405", "Method Not Allowed", "type of request");
            break;
    }
}

function printTaskById($taskId, $link)
{
    $queryResult = $link->query("SELECT * FROM tasks WHERE id='$taskId'");
    $queryResult = $queryResult->fetch_all()[0];
    if ($queryResult === null) {
        printErrorMessage("400", "Bad Request", "id");
    }

    $httpAnwserData = (object)[
        "id" => $queryResult[0],
        "name" => $queryResult[1],
        "topicId" => $queryResult[2],
        "description" => $queryResult[3],
        "price" => $queryResult[4],
        "isDraft" => $queryResult[5]
    ];

    printSuccessMessageWithData(200, "OK", $httpAnwserData);
}

function printTaskByName($taskName, $link)
{
    $queryResult = $link->query("SELECT * FROM tasks WHERE name='$taskName'");
    $queryResult = $queryResult->fetch_all()[0];
    if ($queryResult === null) {
        printErrorMessage("400", "Bad Request", "id");
    }

    $httpAnwserData = (object)[
        "id" => $queryResult[0],
        "name" => $queryResult[1],
        "topicId" => $queryResult[2],
        "description" => $queryResult[3],
        "price" => $queryResult[4],
        "isDraft" => $queryResult[5]
    ];

    printSuccessMessageWithData(200, "OK", $httpAnwserData);
}

function printSolutionByAuthorIdAndTaskId($authorId, $taskId, $link)
{
    $queryResult = $link->query("SELECT * FROM solutions WHERE authorId = '$authorId' AND taskId = '$taskId'");
    $queryResult = $queryResult->fetch_all()[0];
    if ($queryResult === null) {
        printErrorMessage("400", "Bad Request", "id");
    }

    $httpAnwserData = (object)[
        "id" => $queryResult[0],
        "sourceCode" => $queryResult[1],
        "programmingLanguage" => $queryResult[2],
        "verdict" => $queryResult[3],
        "authorId" => $queryResult[4],
        "taskId" => $queryResult[5]
    ];

    printSuccessMessageWithData(200, "OK", $httpAnwserData);
}
