<?php
function printErrorMessage($code, $description, $functionName)
{
    header('HTTP/1.0 ' . $code . ' ' . $description);
    echo json_encode(array(
        'error' => $description,
        'message' => "Something went wrong in method " . $functionName
    ));
}

function printSuccessMessageWithData($code, $description, $data)
{
    header('HTTP/1.0 ' . $code . ' ' . $description);
    echo json_encode($data);
}

function printSuccessMessageWithoutData($code, $description)
{
    header('HTTP/1.0 ' . $code . ' ' . $description);
}