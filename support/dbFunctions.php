<?php
function connectToDataBase()
{
    return mysqli_connect("127.0.0.1:60888", "lab1_user", "lab1_pswd", "lab1");
}

function checkConnectionWithDataBase($link)
{
    if (!$link) {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/support/printFunctions.php');
        printErrorMessage(500, "Internal Server Error", "db connection");
    }
}

function getUserIdByToken($token, $link)
{
    $checkTokenResult = $link->query("SELECT userId FROM authorizationtokens WHERE tokenValue='$token'");

    return $checkTokenResult->fetch_all()[0][0];
}

function checkUserForExistingById($userId, $link)
{
    $link->query("SELECT username FROM users WHERE userId = '$userId'");

    if (mysqli_affected_rows($link) === 0)
        return false;
    return true;
}

function checkUserForExistingByToken($token, $link)
{
    $link->query("SELECT userId FROM authorizationtokens WHERE tokenValue='$token'");

    if (mysqli_affected_rows($link) === 0)
        return false;
    return true;
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

function checkTopicForExistingById($id, $link)
{
    $link->query("SELECT * FROM topics WHERE id='$id'");

    if (mysqli_affected_rows($link) === 0)
        return false;
    return true;
}

function checkTaskForExistingById($id, $link)
{
    $link->query("SELECT * FROM tasks WHERE id='$id'");

    if (mysqli_affected_rows($link) === 0)
        return false;
    return true;
}

function checkSolutionForExistingById($id, $link)
{
    $link->query("SELECT * FROM solutions WHERE id='$id'");

    if (mysqli_affected_rows($link) === 0)
        return false;
    return true;
}