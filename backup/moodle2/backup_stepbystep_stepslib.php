<?php

defined('MOODLE_INTERNAL') || die;

class backup_stepbystep_activity_structure_step extends backup_activity_structure_step
{

    protected function define_structure()
    {
        $stepbystep = new backup_nested_element('stepbystep', array('id'), ['name', 'intro', 'introformat', 'content', 'timemodified']);

        $stepbystep->set_source_table('stepbystep', array('id' => backup::VAR_ACTIVITYID));

        $stepbystep->annotate_files('mod_stepbystep', 'intro', null);
        $stepbystep->annotate_files('mod_page', 'content', null);

        return $this->prepare_activity_structure($stepbystep);
    }

}
