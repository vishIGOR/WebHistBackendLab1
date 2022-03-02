<?php

function route($method, $urlData, $formData)
{
    $formData = (array)$formData;

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    $queryResult = null;
    $httpAnwserData = [];
    switch ($method) {
        case 'GET':
            if ($urlData === []) {
                if (count($formData) <= 1 || count($formData) > 3) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $filterTaskId = $formData["task"];
                $filterUserId = $formData["user"];

                if ($filterTaskId === null) {
                    if ($filterUserId === null) {
                        $queryResult = $link->query("SELECT * FROM solutions");
                    } else {
                        if (!checkStringForNumber($filterUserId)) {
                            printErrorMessage("400", "Bad Request", "userId");
                        }
                        $queryResult = $link->query("SELECT * FROM solutions WHERE authorId = '$filterUserId'");
                    }
                } else {
                    if (!checkStringForNumber($filterTaskId)) {
                        printErrorMessage("400", "Bad Request", "taskId");
                    }
                    if ($filterUserId === null) {
                        $queryResult = $link->query("SELECT * FROM solutions WHERE taskId='$filterTaskId'");
                    } else {
                        if (!checkStringForNumber($filterUserId)) {
                            printErrorMessage("400", "Bad Request", "userId");
                        }
                        $queryResult = $link->query("SELECT * FROM solutions WHERE taskId='$filterTaskId' AND authorId = '$filterUserId'");
                    }
                }

                foreach ($queryResult->fetch_all() as $resultKey => $resultRow) {
                    $httpAnwserData[$resultKey] = (object)[
                        "id" => $resultRow[0],
                        "sourceCode" => $resultRow[1],
                        "programmingLanguage" => $resultRow[2],
                        "verdict" => $resultRow[3],
                        "authorId" => $resultRow[4],
                        "taskId" => $resultRow[5]
                    ];
                }

                printSuccessMessageWithData(200, "OK", $httpAnwserData);
            }

            printErrorMessage("400", "Bad Request", "request");
            break;
        case 'POST':
            if (checkStringForNumber($urlData[0]) && $urlData[1] === "postmoderation") {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", "number of data rows");
                }

                $templateForCheck = array(
                    "verdict" => 0
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

                $solutionId = $urlData[0];
                if (!checkSolutionForExistingById($solutionId, $link)) {
                    printErrorMessage("400", "Bad Request", "solutionId");
                }

                $verdict = $formData["verdict"];
                $queryResult = $link->query("UPDATE solutions SET verdict= '$verdict' WHERE id='$solutionId'");

                if (mysqli_affected_rows($link) === 0 || !$queryResult) {
                    printErrorMessage("400", "Bad Request", "verdict");
                }

                printSolutionById($solutionId, $link);
            }
            printErrorMessage("400", "Bad Request", "request");
            break;
        default:
            printErrorMessage("405", "Method Not Allowed", "type of request");
            break;
    }
}

function printSolutionById($solutionId, $link)
{
    $queryResult = $link->query("SELECT * FROM solutions WHERE id='$solutionId'");
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
