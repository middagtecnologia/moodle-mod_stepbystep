<?php

namespace mod_stepbystep\event;

defined('MOODLE_INTERNAL') || die();

class course_module_viewed extends \core\event\course_module_viewed
{

    protected function init()
    {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'stepbystep';
    }

    public static function get_objectid_mapping()
    {
        return array('db' => 'stepbystep', 'restore' => 'stepbystep');
    }
}
