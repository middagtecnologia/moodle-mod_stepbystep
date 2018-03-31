<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/stepbystep/backup/moodle2/restore_stepbystep_stepslib.php');

class restore_stepbystep_activity_task extends restore_activity_task
{

    protected function define_my_settings()
    {
        // No particular settings for this activity
    }

    protected function define_my_steps()
    {
        $this->add_step(new restore_stepbystep_activity_structure_step('stepbystep_structure', 'stepbystep.xml'));
    }

    static public function define_decode_contents()
    {
        $contents = array();

        $contents[] = new restore_decode_content('stepbystep', array('intro', 'content'), 'stepbystep');

        return $contents;
    }

    static public function define_decode_rules()
    {
        $rules = array();

        $rules[] = new restore_decode_rule('STEPBYSTEPINDEX', '/mod/stepbystep/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('STEPBYSTEPVIEWBYID', '/mod/stepbystep/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('STEPBYSTEPVIEWBYU', '/mod/stepbystep/view.php?step=$1', 'stepbystep');

        return $rules;
    }

    static public function define_restore_log_rules()
    {
        $rules = array();

        $rules[] = new restore_log_rule('stepbystep', 'add', 'view.php?id={course_module}', '{stepbystep}');
        $rules[] = new restore_log_rule('stepbystep', 'update', 'view.php?id={course_module}', '{stepbystep}');
        $rules[] = new restore_log_rule('stepbystep', 'view', 'view.php?id={course_module}', '{stepbystep}');

        return $rules;
    }

    static public function define_restore_log_rules_for_course()
    {
        $rules = array();

        $rules[] = new restore_log_rule('stepbystep', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
