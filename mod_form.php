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

        $key = 0;
        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true];

        if ($stepbystep = $this->current) {
            if (isset($stepbystep->content)) {
                $content = json_decode($stepbystep->content);
                foreach ($content as $value) {
                    $name = "content_$key";
                    $mform->addElement('editor', $name, "Step " . (++$key), null, $editoroptions);
                    $mform->setType($name, PARAM_RAW);
                    $mform->setDefault($name, ['text' => $value, 'format' => FORMAT_HTML]);
                }
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $name = "content_$key";
            $mform->addElement('editor', $name, "Step " . (++$key), null, $editoroptions);
            $mform->setType($name, PARAM_RAW);
        }

        $mform->addRule('content_0', null, 'required', null, 'client');

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {

    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

}
