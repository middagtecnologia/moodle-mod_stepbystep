<?php

class restore_stepbystep_activity_structure_step extends restore_activity_structure_step
{

    protected function define_structure()
    {

        $paths = array();
        $paths[] = new restore_path_element('stepbystep', '/activity/stepbystep');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_stepbystep($data)
    {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the stepbystep record
        $newitemid = $DB->insert_record('stepbystep', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute()
    {
        // Add stepbystep related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_stepbystep', 'intro', null);
    }

}
