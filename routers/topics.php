<?php

function route($method, $urlData, $formData)
{
    $formData = (array)$formData;

    $link = connectToDataBase();
    checkConnectionWithDataBase($link);

    $queryResult = null;
    $httpAnwserData = [];

    switch ($method) {
        case "GET":
            if ($urlData === []) {
                if (count($formData) <= 1 || count($formData) > 3) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $filterName = $formData["name"];
                $filterParentId = $formData["parent"];

                if ($filterParentId === null) {
                    $queryResult = $link->query("SELECT * FROM topics");
                } else {
                    if (!checkStringForNumber($filterParentId)) {
                        printErrorMessage("400", "Bad Request", "parentId");
                    }
                    $queryResult = $link->query("SELECT * FROM topics WHERE parentId = '$filterParentId'");
                }

                if ($filterName === null) {
                    foreach ($queryResult->fetch_all() as $resultKey => $resultRow) {
                        $httpAnwserData[$resultKey] = (object)[
                            "id" => $resultRow[0],
                            "name" => $resultRow[1],
                            "parentId" => $resultRow[2],
                        ];
                    }
                } else {
                    foreach ($queryResult->fetch_all() as $resultKey => $resultRow) {
                        if (preg_match("/" .$filterName . "/", $resultRow[1])) {
                            $httpAnwserData[$resultKey] = (object)[
                                "id" => $resultRow[0],
                                "name" => $resultRow[1],
                                "parentId" => $resultRow[2],
                            ];
                        }
                    }
                }

                printSuccessMessageWithData(200, "OK", $httpAnwserData);
            }


            if (checkStringForNumber($urlData[0])) {
                if (count($formData) !== 1) {
                    printErrorMessage(400, "Bad Request", "number of query rows");
                }

                $topicId = $urlData[0];

                if (!checkTopicForExistingById($topicId, $link)) {
                    printErrorMessage("400", "Bad Request", "parentId");
                }

                if ($urlData[1] === null) {
                    printTopicWithChildsById($topicId, $link);
                }

                if ($urlData[1] === "childs") {
                    $queryResult = $link->query("SELECT * FROM topics WHERE parentId='$topicId'");

                    foreach ($queryResult->fetch_all() as $resultKey => $resultRow) {
                        $httpAnwserData[$resultKey] = (object)[
                            "id" => $resultRow[0],
                            "name" => $resultRow[1],
                            "parentId" => $resultRow[2],
                        ];
                    }
                    printSuccessMessageWithData(200, "OK", $httpAnwserData);
                }
            }

            printErrorMessage("400", "Bad Request", "request");
            break;
        case "POST":
            if ($urlData === []) {
                if (count($formData) !== 2) {
                    printErrorMessage(400, "Bad Request", "number of body rows");
                }

                $templateForCheck = array(
                    "name" => 0,
                    "parentId" => 0
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

                if (!checkTopicForExistingById($formData["parentId"], $link)) {
                    printErrorMessage("400", "Bad Request", "parentId");
                }

                $newName = $formData["name"];
                $newParentId = $formData["parentId"];
                $link->query("INSERT topics(name, parentId) VALUES ('$newName','$newParentId')");

                printTopicWithChildsById($newParentId, $link);
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "childs") {
                $parentId = $urlData[0];

                foreach ($formData as $element) {
                    if (!checkStringForNumber($element)) {
                        printErrorMessage("400", "Bad Request", "array of childs");
                    }
                    if (!checkTopicForExistingById($element, $link)) {
                        printErrorMessage("400", "Bad Request", "parentId");
                    }
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }

                if (!checkTopicForExistingById($parentId, $link)) {
                    printErrorMessage("400", "Bad Request", "parentId");
                }

                foreach ($formData as $element) {
                    $link->query("UPDATE topics SET parentId= '$parentId' WHERE id ='$element'");
                }

                printTopicWithChildsById($parentId, $link);
            }

            printErrorMessage("400", "Bad Request", "request");
            break;
        case "PATCH":
            if (checkStringForNumber($urlData[0]) && $urlData[1] === null) {
                $topicId = $urlData[0];

                if (count($formData) < 1 || count($formData) > 2) {
                    printErrorMessage(400, "Bad Request", "number of body rows");
                }
                $templateForCheck = array(
                    "name" => 0,
                    "parentId" => 0
                );
                if (checkDataRowsForTemplateNotMatchingOrNull($formData, $templateForCheck)) {
                    printErrorMessage(400, "Bad Request", "datarows");
                }
                $name = $formData["name"];
                $parentId = $formData["parentId"];

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }
                
                if (!checkTopicForExistingById($topicId, $link)) {
                    printErrorMessage("400", "Bad Request", "topicId");
                }

                if ($name !== null) {
                    $link->query("UPDATE topics SET name='$name' WHERE id='$topicId'");
                }

                if ($parentId !== null) {
                    if ($parentId === "null") {
                        $link->query("UPDATE topics SET parentId = NULL WHERE id ='$topicId'");
                        printTopicWithChildsById($topicId, $link);
                    } else {
                        if (!checkStringForNumber($parentId)) {
                            printErrorMessage("400", "Bad Request", "parentId");
                        }
                        $link->query("UPDATE topics SET parentId = '$parentId' WHERE id ='$topicId'");
                        printTopicWithChildsById($parentId, $link);
                    }
                } else {
                    printTopicWithChildsById($topicId, $link);
                }
            }
            printErrorMessage("400", "Bad Request", "request");
            break;
        case "DELETE":
            if (checkStringForNumber($urlData[0]) && $urlData[1] === null) {
                $topicId = $urlData[0];

                if (count($formData) !== 0) {
                    printErrorMessage(400, "Bad Request", "number of body rows");
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }
                if (!checkTopicForExistingById($topicId, $link)) {
                    printErrorMessage("400", "Bad Request", "topicId");
                }

                $link->query("DELETE FROM topics WHERE id = '$topicId'");

                if (mysqli_affected_rows($link) === 0) {
                    printErrorMessage("500", "Internal Server Error", "deleting topic");
                }

                printSuccessMessageWithoutData(200, "OK");
            }

            if (checkStringForNumber($urlData[0]) && $urlData[1] === "childs") {
                $parentId = $urlData[0];

                foreach ($formData as $element) {
                    if (!checkStringForNumber($element)) {
                        printErrorMessage("400", "Bad Request", "array of childs");
                    }
                    if (!checkTopicForExistingById($element, $link)) {
                        printErrorMessage("400", "Bad Request", "parentId");
                    }
                }

                if (!$token = checkTokenType("Bearer")) {
                    printErrorMessage(403, "Forbidden", "token");
                }

                $checkForAdminRole = checkUserForAdminRoleByToken($token, $link);
                if ($checkForAdminRole !== true) {
                    printErrorMessage($checkForAdminRole[0], $checkForAdminRole[1], $checkForAdminRole[2]);
                }


                if (!checkTopicForExistingById($parentId, $link)) {
                    printErrorMessage("400", "Bad Request", "parentId");
                }

                foreach ($formData as $element) {
                    $link->query("DELETE FROM topics WHERE id = '$element' AND parentId='$parentId'");
                }

                printTopicWithChildsById($parentId, $link);
            }

            printErrorMessage("400", "Bad Request", "request");
            break;
        default:
            printErrorMessage("405", "Method Not Allowed", "type of request");
            break;
    }
}

function printTopicWithChildsById($topicId, $link)
{
    $queryResult = $link->query("SELECT * FROM topics WHERE id='$topicId'");
    $queryResult = $queryResult->fetch_all()[0];
    if ($queryResult === null) {
        printErrorMessage("400", "Bad Request", "id");
    }

    $httpAnwserData = (object)[
        "id" => $queryResult[0],
        "name" => $queryResult[1],
        "parentId" => $queryResult[2],
    ];

    $queryResult = $link->query("SELECT * FROM topics WHERE parentId='$topicId'");
    $httpAnwserData->childs = [];
    foreach ($queryResult->fetch_all() as $resultKey => $resultRow) {
        $httpAnwserData->childs[$resultKey] = (object)[
            "id" => $resultRow[0],
            "name" => $resultRow[1],
            "parentId" => $resultRow[2],
        ];
    }
    printSuccessMessageWithData(200, "OK", $httpAnwserData);
}
