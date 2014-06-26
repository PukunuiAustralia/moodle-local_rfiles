<?php
/**
 * Push/pull remote files on a schedule
 *
 * Version information
 *
 * @package     local
 * @subpackage  rfiles
 * @author      Shane Elliott, shane@pukunui.com
 * @copyright   Pukunui, http://pukunui.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2014062302;
$plugin->requires  = 2012110100;
$plugin->component = 'local_rfiles';
$plugin->cron      = 60;
