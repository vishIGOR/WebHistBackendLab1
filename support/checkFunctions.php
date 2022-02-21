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
