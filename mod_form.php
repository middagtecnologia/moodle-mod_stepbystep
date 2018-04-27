<?php

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_stepbystep_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $config = get_config('stepbystep');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);
        //-------------------------------------------------------
        $mform->addElement('header', 'textcontent', get_string('content'));

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('editor', 'content', get_string('stepno', 'mod_stepbystep'), null, stepbystep_get_editor_options($this->context));

        $repeat = 1;
        if ($this->current->instance) {
            $repeat = count(json_decode($this->current->content));
        }

        $this->repeat_elements($repeatarray, $repeat, ['type'=> PARAM_RAW], 'content_repeats', 'content_add', 2, null, true);

        $mform->addRule('content[0]', null, 'required', null, 'client');

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values)
    {
        if ($this->current->instance) {
            $contents = json_decode($default_values['content']);
            foreach ($contents as $key => $content) {
                $name = "content[$key]";
                $draftitemid = file_get_submitted_draft_itemid($name);
                $default_values[$name]['format'] = $default_values['contentformat'];
                $default_values[$name]['text'] = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_stepbystep', 'content', 0, stepbystep_get_editor_options($this->context), $content);
                $default_values[$name]['itemid'] = $draftitemid;
            }
        }
    }

}
