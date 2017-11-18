<?php

require('../../config.php');

$id = required_param('id', PARAM_INT); // course
$PAGE->set_url('/mod/stepbystep/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

//$params = array(
//    'context' => context_course::instance($course->id)
//);
//$event = \mod_url\event\course_module_instance_list_viewed::create($params);
//$event->add_record_snapshot('course', $course);
//$event->trigger();

$strurl       = get_string('modulename', 'mod_stepbystep');
$strurls      = get_string('modulename', 'mod_stepbystep');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_title($course->shortname.': '.$strurls);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strurls);
echo $OUTPUT->header();
echo $OUTPUT->heading($strurls);

if (!$stepbysteps = get_all_instances_in_course('stepbysteps', $course)) {
    notice(get_string('thereareno', 'moodle', $strurls), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($stepbysteps as $stepbystep) {
    $cm = $modinfo->cms[$stepbystep->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($stepbystep->section !== $currentsection) {
            if ($stepbystep->section) {
                $printsection = get_section_name($course, $stepbystep->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $stepbystep->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($stepbystep->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        // each url has an icon in 2.0
        $icon = $OUTPUT->pix_icon($cm->icon, get_string('modulename', $cm->modname)) . ' ';
    }

    $class = $stepbystep->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($stepbystep->name)."</a>",
        format_module_intro('url', $stepbystep, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
