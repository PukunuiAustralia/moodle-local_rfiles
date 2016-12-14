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
require_once($CFG->libdir.'/formslib.php');

/**
 * Defining hosts for remote transfers
 */
class rfiles_hostform extends moodleform {
    
    /**
     * Form definition
     */
    public function definition() {
        
        $mform =& $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('connectionname', 'local_rfiles'), 'size="40"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', $strrequired, 'required', '', 'client');

        $mform->addElement('checkbox', 'active', get_string('active', 'local_rfiles'));
        $mform->setType('active', PARAM_INT);
        $mform->setDefault('active', 1);

        $operationoptions = array(RFILES_OP_PUSH => get_string('operationput', 'local_rfiles'),
                                  RFILES_OP_PULL => get_string('operationget', 'local_rfiles')
                                 );
        $mform->addElement('select', 'operation', get_string('operation', 'local_rfiles'), $operationoptions);
        $mform->setType('operation', PARAM_INT);
        $mform->setDefault('operation', RFILES_OP_PUSH);
        $mform->addRule('operation', $strrequired, 'required', '', 'client');

        $protocoloptions = array(RFILES_PR_FTP     => get_string('protocolftp', 'local_rfiles'),
                                 RFILES_PR_FTPPASV => get_string('protocolftppasv', 'local_rfiles'),
                                 RFILES_PR_FTPSSL  => get_string('protocolssl', 'local_rfiles'),
                                 RFILES_PR_SFTP    => get_string('protocolsftp', 'local_rfiles')
                                );
        $mform->addElement('select', 'protocol', get_string('protocol', 'local_rfiles'), $protocoloptions);
        $mform->setType('protocol', PARAM_INT);
        $mform->setDefault('protocol', RFILES_PR_FTP);
        $mform->addRule('protocol', $strrequired, 'required', '', 'client');

        $mform->addElement('text', 'host', get_string('remotehost', 'local_rfiles'), 'size="40"');
        $mform->setType('host', PARAM_HOST);
        $mform->addRule('host', $strrequired, 'required', '', 'client');

        $mform->addElement('text', 'port', get_string('remoteport', 'local_rfiles'), 'size="5"');
        $mform->setType('port', PARAM_INT);

        $mform->addElement('text', 'username', get_string('remoteusername', 'local_rfiles'), 'size="10"');
        $mform->setType('username', PARAM_RAW);

        $mform->addElement('passwordunmask', 'password', get_string('remotepassword', 'local_rfiles'), 'size="10"');
        $mform->setType('password', PARAM_RAW);

        $mform->addElement('text', 'remotedirectory', get_string('remotedirectory', 'local_rfiles'), 'size="40"');
        $mform->setType('remotedirectory', PARAM_PATH);
        $mform->addRule('remotedirectory', $strrequired, 'required', '', 'client');

        $mform->addElement('text', 'localdirectory', get_string('localdirectory', 'local_rfiles'), 'size="40"');
        $mform->setType('localdirectory', PARAM_TEXT);
        $mform->addRule('localdirectory', $strrequired, 'required', '', 'client');

        $mform->addElement('text', 'pattern', get_string('filepattern', 'local_rfiles'), 'size="20"');
        $mform->setType('pattern', PARAM_RAW);

        $efoptions = array(RFILES_EF_SOURCE      => get_string('efsource', 'local_rfiles'),
                           RFILES_EF_LATEST      => get_string('eflatest', 'local_rfiles'),
                           RFILES_EF_DESTINATION => get_string('efdestination', 'local_rfiles')
                          );
        $mform->addElement('select', 'existingfiles', get_string('existingfiles', 'local_rfiles'), $efoptions);
        $mform->setType('existingfiles', PARAM_INT);
        $mform->setDefault('existingfiles', RFILES_EF_SOURCE);
        $mform->addRule('existingfiles', $strrequired, 'required', '', 'client');

        $srcoptions = array(RFILES_SRC_LEAVE  => get_string('srcleave', 'local_rfiles'),
                            RFILES_SRC_MOVE   => get_string('srcmove', 'local_rfiles'),
                            RFILES_SRC_DELETE => get_string('srcdelete', 'local_rfiles')
                           );
        $mform->addElement('select', 'sourcefileaction', get_string('sourcefileaction', 'local_rfiles'), $srcoptions);
        $mform->setType('sourcefileaction', PARAM_INT);
        $mform->setDefault('sourcefileaction', RFILES_SRC_LEAVE);
        $mform->addRule('sourcefileaction', $strrequired, 'required', '', 'client');

        $mform->addElement('text', 'sourceprocessdirectory', get_string('sourceprocessdirectory', 'local_rfiles'), 'size="40"');
        $mform->setType('sourceprocessdirectory', PARAM_PATH);
        $mform->disabledIf('sourceprocessdirectory', 'sourcefileaction', 'neq', RFILES_SRC_MOVE);

        $freqoptions = array(     1 => get_string('freqasap', 'local_rfiles'),
                                900 => get_string('freq15min', 'local_rfiles'),
                               3600 => get_string('freqhour', 'local_rfiles'),
                              86400 => get_string('freqday', 'local_rfiles'),
                             604800 => get_string('freqweek', 'local_rfiles')
                            );
        $mform->addElement('select', 'frequency', get_string('frequency', 'local_rfiles'), $freqoptions);
        $mform->setType('frequency', PARAM_INT);
        $mform->setDefault('frequency', 86400);
        $mform->addRule('frequency', $strrequired, 'required', '', 'client');

        $this->add_action_buttons(true);
    }

    /**
     * Validate data
     *
     * @param array $data  submitted form data
     * @param array $files  submitted files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check the local directory.
        if ($data['localdirectory'] !== $this->_clean_file_path($data['localdirectory'])) {
            $errors['localdirectory'] = get_string('errorlocaldirectoryformat', 'local_rfiles');
        } else if (!check_dir_exists(rtrim($data['localdirectory'], '/'))) {
            $errors['localdirectory'] = get_string('errorlocaldirectory', 'local_rfiles');
        }

        return $errors;
    }

    /**
     * Internal function to check for valid characters in a directory path.
     * We rely on this rather than PARAM_PATH so that we can have windows drive letters
     * Assumption: the data is being cleaned by the param type PARAM_TEXT so there is no need to reproduce work.
     * 
     * @param string $param  the submitted directory path
     * @return string
     */
    private function _clean_file_path($param) {
        return preg_replace('/[^a-zA-Z0-9:\/\\\\_-]/i', '', $param);
    }

}
