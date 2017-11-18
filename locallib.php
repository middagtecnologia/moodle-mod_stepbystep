<?php

function mod_stepbystep_content_process($contentjson)
{
    $content = json_decode($contentjson);
    $data = [];
    $nav = false;

    if (is_array($content)) {
        foreach ($content as $item) {
            $data[] = ['content' => $item];
        }
    }

    if (!empty($data)) {
        $data[0]['active'] = true;
        if (count($data) > 1) {
            $nav = true;
        }
    }

    return [$data, $nav];
}

function mod_stepbystep_content_save($data)
{
    $content = [];
    for ($i = 0; $i < 300; $i++) {
        $name = "content_$i";
        if (property_exists($data, $name)) {
            if (empty(trim($data->$name['text']))) {
                continue;
            } else {
                $content[] = $data->$name['text'];
            }
        }
    }
    return json_encode($content);
}
