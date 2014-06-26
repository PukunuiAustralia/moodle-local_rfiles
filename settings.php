<?php
/**
 * Push/pull remote files on a schedule
 *
 * Settings page
 *
 * @package     local
 * @subpackage  rfiles
 * @author      Shane Elliott, shane@pukunui.com
 * @copyright   Pukunui, http://pukunui.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_externalpage('local_rfilessettings',
            get_string('pluginname', 'local_rfiles'),
            new moodle_url('/local/rfiles/index.php')));
}
