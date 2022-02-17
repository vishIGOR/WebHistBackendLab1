<?php
function connectToDataBase()
{
    return mysqli_connect("127.0.0.1:60888", "lab1_user", "lab1_pswd", "lab1");
}

function checkConnectionWithDataBase($link)
{
    if (!$link) {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/support/printFunctions.php');
        printErrorMessage(500, "Internal Server Error", __FUNCTION__);
        exit;
    }
}
