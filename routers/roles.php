<?php
function route($method, $urlData, $formData)
{
    $formData = (array)$formData;

    switch ($method) {
        case 'GET':
            if (count($formData) !== 1) {
                printErrorMessage(400, "Bad Request", "number of rows");
            }
            
            if (!$token = checkTokenType("Bearer")) {
                printErrorMessage(403, "Forbidden", "token");
            }

            $link = connectToDataBase();
            checkConnectionWithDataBase($link);

            if (!checkUserForExistingByToken($token, $link)) {
                printErrorMessage(403, "Forbidden", "token");
            }


            if ($urlData === []) {
                $getAllRolesResult = $link->query("SELECT * FROM roles");

                $httpAnwserData = [];
                foreach ($getAllRolesResult->fetch_all() as $resultKey => $resultRow) {
                    $httpAnwserData[$resultKey] = (object)[
                        "roleId" => $resultRow[0],
                        "name" => $resultRow[1]
                    ];
                }
                printSuccessMessageWithData(200, "OK", $httpAnwserData);
            } else {
                if (!checkStringForNumber($urlData[0])) {
                    printErrorMessage("400", "Bad Request", "roleId");
                }

                $roleId = $urlData[0];

                $getRoleByIdResult = $link->query("SELECT * FROM roles WHERE roleId='$roleId'");
                $roleFromDatabase = $getRoleByIdResult->fetch_all()[0];

                if ($roleFromDatabase === null) {
                    printErrorMessage(400, "Bad Request", "roleId");
                }

                $httpAnwserData = (object)[
                    "roleId" => $roleFromDatabase[0],
                    "name" => $roleFromDatabase[1]
                ];
                printSuccessMessageWithData(200, "OK", (object)$httpAnwserData);
            }
            break;

        default:
            printErrorMessage("405", "Method Not Allowed", "type of request");
            break;
    }
}
