<?php
/**
 * Push/pull remote files on a schedule
 *
 * Main landing page
 *
 * @package     local
 * @subpackage  rfiles
 * @author      Shane Elliott, shane@pukunui.com
 * @copyright   Pukunui, http://pukunui.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');

$systemcontext = context_system::instance();

require_login();
require_capability('moodle/site:config', $systemcontext);

$strrfiles = get_string('pluginname', 'local_rfiles');

$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($strrfiles);
$PAGE->set_cacheable(false);
$PAGE->set_popup_notification_allowed(false);
$PAGE->set_heading($strrfiles);
$PAGE->set_url('/local/rfiles/index.php');
$PAGE->navbar->add($strrfiles);

$table = local_rfiles_get_hosts_table();


echo $OUTPUT->header();
echo $OUTPUT->heading($strrfiles);
echo $OUTPUT->single_button($CFG->wwwroot.'/local/rfiles/editconnection.php', get_string('add'));
echo html_writer::table($table);
echo $OUTPUT->single_button($CFG->wwwroot.'/local/rfiles/editconnection.php', get_string('add'));
echo $OUTPUT->footer();

