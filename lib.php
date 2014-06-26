<?php
/**
 * Push/pull remote files on a schedule
 *
 * Library functions
 *
 * @package     local
 * @subpackage  rfiles
 * @author      Shane Elliott, shane@pukunui.com
 * @copyright   Pukunui, http://pukunui.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/rfiles/classes.php');


// Define various constants

/**
 * Operation PUSH (PUT)
 */
define('RFILES_OP_PUSH', 1);

/**
 * Operation PULL (GET)
 */
define('RFILES_OP_PULL', 2);

/**
 * Remote protocol FTP
 */
define('RFILES_PR_FTP', 1);

/**
 * Remote protocol FTP PASV mode
 */
define('RFILES_PR_FTPPASV', 2);

/**
 * Remote protocol FTP-SSL
 */
define('RFILES_PR_FTPSSL', 3);

/**
 * Remote protocol SFTP
 */
define('RFILES_PR_SFTP', 4);

/**
 * Existing files overwrite - source file wins
 */
define('RFILES_EF_SOURCE', 1);

/**
 * Existing files keep latest
 */
define('RFILES_EF_LATEST', 2);

/**
 * Existing files never overwrite - destination file wins
 */
define('RFILES_EF_DESTINATION', 3);

/**
 * Leave source files after move
 */
define('RFILES_SRC_LEAVE', 1);

/**
 * Move source files to another directory
 */
define('RFILES_SRC_MOVE', 2);

/**
 * Delete source files
 */
define('RFILES_SRC_DELETE', 3);


/**
 * Cron function
 *
 * @return boolean
 */
function local_rfiles_cron() {
    global $DB;
    if ($hosts = local_rfiles_get_hosts_to_process()) {
        $now = time();
        foreach ($hosts as $h) {
            $host = new rfiles_host($h->id);
            if ($host->is_logged_in()) {
                $count = $host->transfer();
                mtrace($host->get_connection_name().': '.$count.' files');
            } else {
                mtrace($host->get_connection_name().': Failed to connect');
            }

            $DB->set_field('local_rfiles_host', 'lastcron', $now, array('id' => $h->id));
            unset($host);
        }
    }
}

/**
 * Return the hosts to process given the current timestamp
 *
 * @param integer $timestamp  (optional) UTC time, defaults to now
 * @return array  list of ids (integers)
 */
function local_rfiles_get_hosts_to_process($timestamp=0) {
    global $DB;

    if (empty($timestamp)) {
        $timestamp = time();
    }
    
    return $DB->get_records_select('local_rfiles_host', '((? - lastcron) > frequency) AND active=1', array($timestamp), 'id ASC', 'id, protocol');
}

/**
 * Return a list of hosts
 *
 * @return html_table
 */
function local_rfiles_get_hosts_table() {
    global $DB, $CFG;

    $table = new html_table();
    $table->attributes = array('class' => 'generaltable rfiles_hostlist');
    
    if ($hosts = $DB->get_records('local_rfiles_host', null, '', 'id, active, name, protocol, operation, host')) {
        foreach ($hosts as $h) {
            $row = new html_table_row();

            $cell = new html_table_cell();
            $cell->text = html_writer::link($CFG->wwwroot.'/local/rfiles/editconnection.php?id='.$h->id, $h->name);
            $row->cells[] = $cell;


            $cell = new html_table_cell();
            $cell->text = $h->host;
            $row->cells[] = $cell;

            $table->data[] = $row;
        }
    }

    return $table;
}
