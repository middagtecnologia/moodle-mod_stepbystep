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

function stepbystep_add_instance($data, $mform = null)
{
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;

    $data->id = $DB->insert_record('stepbystep', $data);
    $data->timemodified = time();

    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));

    $context = \context_module::instance($cmid);

    $content = [];
    if ($mform) {
        $contents = $data->content;
        foreach ($contents as $key => $item) {
            if (!empty(trim($item['text']))) {
                $draftitemid = $item['itemid'];
                if ($draftitemid) {
                    $content[] = file_save_draft_area_files($draftitemid, $context->id, 'mod_stepbystep', 'content', 0, stepbystep_get_editor_options($context), $item['text']);
                }
            }
        }
    }

    $data->content = json_encode($content);

    $DB->update_record('stepbystep', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'stepbystep', $data->id, $completiontimeexpected);

    return $data->id;
}

function stepbystep_update_instance($data, $mform)
{
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;

    $data->id = $data->instance;
    $data->timemodified = time();

    $context = context_module::instance($cmid);

    $content = [];
    $contents = $data->content;
    foreach ($contents as $key => $item) {
        if (!empty(trim($item['text']))) {
            $draftitemid = $item['itemid'];
            if ($draftitemid) {
                $content[] = file_save_draft_area_files($draftitemid, $context->id, 'mod_stepbystep', 'content', 0, stepbystep_get_editor_options($context), $item['text']);
            }
        }
    }

    $data->content = json_encode($content);

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

function stepbystep_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('content', 'stepbystep');
    return $areas;
}

function stepbystep_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_stepbystep', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_stepbystep', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/stepbystep/locallib.php");
        return new stepbystep_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    return null;
}

function stepbystep_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/stepbystep:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        return false;
    }

    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // serve stepbystep content
        $filename = $arg;

        if (!$stepbystep = $DB->get_record('stepbystep', array('id'=>$cm->instance), '*', MUST_EXIST)) {
            return false;
        }

        // We need to rewrite the pluginfile URLs so the media filters can work.
        $content = file_rewrite_pluginfile_urls($stepbystep->content, 'webservice/pluginfile.php', $context->id, 'mod_stepbystep', 'content', 0);
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $content = format_text($content, $stepbystep->contentformat, $formatoptions);

        // Remove @@PLUGINFILE@@/.
        $options = array('reverse' => true);
        $content = file_rewrite_pluginfile_urls($content, 'webservice/pluginfile.php', $context->id, 'mod_stepbystep', 'content', 0, $options);
        $content = str_replace('@@PLUGINFILE@@/', '', $content);

        send_file($content, $filename, 0, 0, true, true);
    } else {
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_stepbystep/$filearea/0/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, null, 0, $forcedownload, $options);
    }
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

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_stepbystep', 'content', 0, 'sortorder DESC, id ASC', false);
    foreach ($files as $fileinfo) {
        $file = array();
        $file['type']         = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_stepbystep/content/0'.$fileinfo->get_filepath().$fileinfo->get_filename(), true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $file['mimetype']     = $fileinfo->get_mimetype();
        $file['isexternalfile'] = $fileinfo->is_external_file();
        if ($file['isexternalfile']) {
            $file['repositorytype'] = $fileinfo->get_repository_type();
        }
        $contents[] = $file;
    }

    $item = array();
    $item['type'] = 'file';
    $item['filename'] = clean_param(format_string($stepbysteprecord->name), PARAM_FILE);
    $item['filepath'] = '/';
    $item['filesize'] = 0;
    $item['fileurl'] = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_stepbystep/content/' . $filename, true);
    $item['timecreated'] = null;
    $item['timemodified'] = $stepbysteprecord->timemodified;
    $item['sortorder'] = 1;
    $item['userid'] = null;
    $item['author'] = null;
    $item['license'] = null;
    $contents[] = $item;

    return $contents;
}

function stepbystep_dndupload_register()
{
    return array('types' => array(
        array('identifier' => 'text/html', 'message' => get_string('createstep', 'stepbystep')),
        array('identifier' => 'text', 'message' => get_string('createstep', 'stepbystep'))
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
    if (isset($uploadinfo)) {
        $data->content = $uploadinfo->content;
    }
    $data->timemodified = time();
    $data->coursemodule = $uploadinfo->coursemodule;

    return stepbystep_add_instance($data, null);
}

function stepbystep_view($stepbystep, $course, $cm, $context)
{
    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $stepbystep->id
    );

    $event = \mod_stepbystep\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('stepbystep', $stepbystep);
    $event->trigger();

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
