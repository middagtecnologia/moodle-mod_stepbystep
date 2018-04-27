<?php

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/stepbystep/lib.php");

class stepbystep_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

function stepbystep_get_editor_options($context)
{
    global $CFG;

    return array('subdirs' => 1, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => -1, 'changeformat' => 1, 'context' => $context, 'noclean' => 1, 'trusttext' => 0);
}

function stepbystep_content_process($contentjson, $context)
{
    $content = json_decode($contentjson);
    $data = [];
    $nav = false;

    if (is_array($content)) {
        foreach ($content as $item) {
            $data[] = ['content' => $content = file_rewrite_pluginfile_urls($item, 'pluginfile.php', $context->id, 'mod_stepbystep', 'content', 0)];
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
