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
require_once('forms.php');

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
$PAGE->set_url('/local/rfiles/editconnection.php');
$PAGE->navbar->add($strrfiles);

$id = optional_param('id', 0, PARAM_INT);

$hostform = new rfiles_hostform();

if ($data = $hostform->get_data()) {
    if (empty($data->active)) $data->active = 0; // Set value for empty checkbox.
   
    if (empty($data->port)) {
        switch ($data->protocol) {
            case RFILES_PR_FTP:
            case RFILES_PR_FTPPASV:
            case RFILES_PR_FTPSSL:
                $data->port = 21;
                break;
            case RFILES_PR_SFTP:
                $data->port = 22;
                break;
            default:
                $data->port = 21;
        }
    }

    if (empty($data->username)) $data->username = '';
    if (empty($data->password)) $data->password = '';

    $data->remotedirectory = rtrim($data->remotedirectory, '/');
    $data->localdirectory = rtrim($data->localdirectory, '/');
    $data->sourceproccessdirectory = rtrim($data->sourceprocessdirectory, '/');

    if ($data->id) {
        if ($DB->update_record('local_rfiles_host', $data)) {
            $message = get_string('connectionupdated', 'local_rfiles');
        } else {
            $message = get_string('connectionupdateproblem', 'local_rfiles');
        }
    } else {
        if ($DB->insert_record('local_rfiles_host', $data)) {
            $message = get_string('connectionadded', 'local_rfiles');
        } else {
            $message = get_string('connectionaddedproblem', 'local_rfiles');
        }
    }

    redirect($CFG->wwwroot.'/local/rfiles/index.php', $message);
    exit;
}

// Set the default data.
if ($id) {
    if ($host = $DB->get_record('local_rfiles_host', array('id' => $id))) {
        $hostform->set_data($host);
    }
}

echo $OUTPUT->header();
$hostform->display();
echo $OUTPUT->footer();
