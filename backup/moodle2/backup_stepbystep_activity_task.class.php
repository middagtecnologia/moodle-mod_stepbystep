<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/stepbystep/backup/moodle2/backup_stepbystep_stepslib.php');

class backup_stepbystep_activity_task extends backup_activity_task
{

    protected function define_my_settings()
    {
    }

    protected function define_my_steps()
    {
        $this->add_step(new backup_stepbystep_activity_structure_step('stepbystep_structure', 'stepbystep.xml'));
    }

    static public function encode_content_links($content)
    {
        global $CFG;

        $base = preg_quote($CFG->wwwroot . '/mod/stepbystep', '#');

        //Access a list of all links in a course
        $pattern = '#(' . $base . '/index\.php\?id=)([0-9]+)#';
        $replacement = '$@STEPBYSTEPINDEX*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        //Access the link supplying a course module id
        $pattern = '#(' . $base . '/view\.php\?id=)([0-9]+)#';
        $replacement = '$@STEPBYSTEPVIEWBYID*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        //Access the link supplying an instance id
        $pattern = '#(' . $base . '/view\.php\?u=)([0-9]+)#';
        $replacement = '$@STEPBYSTEPVIEWBYU*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        return $content;
    }
}
