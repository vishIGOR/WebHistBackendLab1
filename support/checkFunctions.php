<?php

function checkDataRowsForNull($data, $template)
{
    foreach (array_keys($template) as $templateKey) {
        if ($data[$templateKey] === '' || $data[$templateKey] === null) {
            return true;
        }
    }
    return false;
}

function checkDataRowsForTemplateNotMatchingOrNull($data, $template)
{
    foreach (array_keys($data) as $dataKey) {
        if ($template[$dataKey] === null) {
            return true;
            if ($data[$dataKey] === '' || $data[$dataKey] === null) {
                return true;
            }
        }
    }
    return false;
}

function checkTokenType($type)
{
    $token = getallheaders()["Authorization"];
    if (substr($token, 0, 7) === $type . " ") {
        return substr($token, 7);
    }
    return false;
}

function checkStringForNumber($value){
    return preg_match('/^([1-9][0-9]*|0)$/', $value);
}