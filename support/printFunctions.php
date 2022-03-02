<?php
function printErrorMessage($code, $description, $functionName)
{
    header('HTTP/1.0 ' . $code . ' ' . $description);
    echo json_encode(array(
        'error' => $description,
        'message' => "Something went wrong with " . $functionName
    ));
    exit;
}

function printSuccessMessageWithData($code, $description, $data)
{
    header('HTTP/1.0 ' . $code . ' ' . $description);
    echo json_encode($data);
    exit;
}

function printSuccessMessageWithoutData($code, $description)
{
    header('HTTP/1.0 ' . $code . ' ' . $description);
    exit;
}

function printSuccessMessageWithFile($code, $description, $filepath)
{
    header('HTTP/1.0 ' . $code . ' ' . $description);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . basename($filepath));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}
