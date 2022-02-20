<?php

function checkDataRowsForNull($data, $template)
{
    foreach (array_keys($template) as $templateRow) {
        if ($data[$templateRow] === '' || $data[$templateRow] === null) {
            return true;
        }
    }
    return false;
}


