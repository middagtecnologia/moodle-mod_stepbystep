<?php

defined('MOODLE_INTERNAL') || die;

require_once 'locallib.php';

function stepbystep_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

function stepbystep_get_extra_capabilities()
{
    return array('moodle/site:accessallgroups');
}

function stepbystep_reset_userdata($data)
{
    return array();
}

function stepbystep_get_view_actions()
{
    return array('view', 'view all');
}

function stepbystep_get_post_actions()
{
    return array('update', 'add');
}

function stepbystep_add_instance($data, $mform)
{
    global $CFG, $DB;

    $data->content = mod_stepbystep_content_save($data);
    $data->timemodified = time();
    $data->id = $DB->insert_record('stepbystep', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'stepbystep', $data->id, $completiontimeexpected);

    return $data->id;
}

function stepbystep_update_instance($data, $mform)
{
    global $CFG, $DB;

    $data->content = mod_stepbystep_content_save($data);
    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('stepbystep', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'stepbystep', $data->id, $completiontimeexpected);

    return true;
}

function stepbystep_delete_instance($id)
{
    global $DB;

    if (!$stepbystep = $DB->get_record('stepbystep', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('stepbystep', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'stepbystep', $id, null);

    $DB->delete_records('stepbystep', array('id' => $stepbystep->id));

    return true;
}

function stepbystep_get_coursemodule_info($coursemodule)
{
    global $CFG, $DB;

    if (!$stepbystep = $DB->get_record('stepbystep', array('id' => $coursemodule->instance), 'id, name, intro, introformat, content')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $stepbystep->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('stepbystep', $stepbystep, $coursemodule->id, false);
    }

    return $info;
}

function stepbystep_page_type_list($pagetype, $parentcontext, $currentcontext)
{
    $module_pagetype = array('mod-stepbystep-*' => get_string('page-mod-stepbystep-x', 'stepbystep'));
    return $module_pagetype;
}

function stepbystep_export_contents($cm, $baseurl)
{
    global $CFG, $DB;

    $contents = array();
    $context = context_module::instance($cm->id);

    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $stepbysteprecord = $DB->get_record('stepbystep', array('id' => $cm->instance), '*', MUST_EXIST);

    $fullurl = str_replace('&amp;', '&', stepbystep_get_full_url($stepbysteprecord, $cm, $course));
    $isurl = clean_param($fullurl, PARAM_URL);
    if (empty($isurl)) {
        return null;
    }

    $item = array();
    $item['type'] = 'url';
    $item['filename'] = clean_param(format_string($stepbysteprecord->name), PARAM_FILE);
    $item['filepath'] = null;
    $item['filesize'] = 0;
    $item['fileurl'] = $fullurl;
    $item['timecreated'] = null;
    $item['timemodified'] = $stepbysteprecord->timemodified;
    $item['sortorder'] = null;
    $item['userid'] = null;
    $item['author'] = null;
    $item['license'] = null;
    $contents[] = $item;

    return $contents;
}

function stepbystep_dndupload_register()
{
    return array('types' => array(
        array('identifier' => 'text/html', 'message' => get_string('createpage', 'page')),
        array('identifier' => 'text', 'message' => get_string('createpage', 'page'))
    ));
}

function stepbystep_dndupload_handle($uploadinfo)
{
    // Gather all the required data.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>' . $uploadinfo->displayname . '</p>';
    $data->introformat = FORMAT_HTML;
    $data->timemodified = time();

    $config = get_config('stepbystep');

    return stepbystep_add_instance($data, null);
}

function stepbystep_view($stepbystep, $course, $cm, $context)
{

    // TODO Trigger course_module_viewed event.

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

function stepbystep_check_updates_since(cm_info $cm, $from, $filter = array())
{
    $updates = course_check_module_updates_since($cm, $from, array('content'), $filter);
    return $updates;
}

function mod_stepbystep_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory)
{
    $cm = get_fast_modinfo($event->courseid)->instances['stepbystep'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/stepbystep/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
