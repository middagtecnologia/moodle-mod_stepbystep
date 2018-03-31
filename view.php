<?php

require('../../config.php');
require('locallib.php');
require_once($CFG->libdir . "/completionlib.php");

$id = optional_param('id', 0, PARAM_INT);
$step = optional_param('step', 0, PARAM_INT);

if ($step) {
    $stepbystep = $DB->get_record('stepbystep', ['id' => $step], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('stepbystep', $stepbystep->id, $stepbystep->course, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('stepbystep', $id, 0, false, MUST_EXIST);
    $stepbystep = $DB->get_record('stepbystep', ['id'=>$cm->instance], '*', MUST_EXIST);
}

if (!$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST)) {
    print_error('invalidcourseid');
}

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stepbystep:view', $context);

$event = \mod_stepbystep\event\course_module_viewed::create(['context' => $context, 'objectid' => $stepbystep->id]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('stepbystep', $stepbystep);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/stepbystep/view.php', array('id' => $cm->id));
$PAGE->set_title($stepbystep->name);
$PAGE->set_heading($course->fullname);
$PAGE->requires->js_call_amd('mod_stepbystep/view', 'init');

$courselink = new single_button(new moodle_url('/course/view.php', ['id' => $course->id]), get_string('returntocourse', 'lesson'), 'get');
list($data, $nav) = mod_stepbystep_content_process($stepbystep->content);
if (trim(strip_tags($stepbystep->intro))) {
    $intro = format_module_intro('stepbystep', $stepbystep, $cm->id);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_stepbystep/stepbystep', [
    'courselink' => $OUTPUT->render($courselink),
    'title' => $stepbystep->name,
    'intro' => $intro,
    'data' => $data,
    'nav' => $nav,
]);
echo $OUTPUT->footer();
