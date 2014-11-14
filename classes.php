<?php
/**
 * Push/pull remote files on a schedule
 *
 * Class definitions
 *
 * @package     local
 * @subpackage  rfiles
 * @author      Shane Elliott, shane@pukunui.com
 * @copyright   Pukunui, http://pukunui.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Define a generic host object
 */
class rfiles_host {
    
    var $config   = null;
    var $handler  = null;
    var $loggedin = false;


    /**
     * Object constructor
     */
    public function __construct($id) {
        global $DB;

        $this->config = $DB->get_record('local_rfiles_host', array('id' => $id));
        if ($this->_connect()) {
            $this->_login();
        }
    }

    /**
     * Object destructor
     */
    public function __destruct() {
        @ftp_close($this->handler);
    }

    /**
     * Make a connection
     *
     * @return boolean  did we make an FTP connection?
     */
    protected function _connect() {
        switch ($this->config->protocol) {
            case RFILES_PR_FTP:
            case RFILES_PR_FTPPASV:
                $this->handler = ftp_connect($this->config->host, $this->config->port);
                break;
            case RFILES_PR_FTPSSL:
                $this->handler = ftp_ssl_connect($this->config->host, $this->config->port);
                break;
            case RFILES_PR_SFTP:
                // TODO
                $this->handler = null;
                break;
            default:
        }
        return (!empty($this->handler));
    }

    /**
     * Log in to remote server
     */
    protected function _login() {

        if (!empty($this->handler)) {
            switch ($this->config->protocol) {
                case RFILES_PR_FTP:
                case RFILES_PR_FTPPASV:
                case RFILES_PR_FTPSSL:
                    $this->loggedin = @ftp_login($this->handler, $this->config->username, $this->config->password);
                    break;
                case RFILES_PR_SFTP:
                    // TODO
                    $this->loggedin = false;
                    break;
                default;
            }

            if ($this->config->protocol == RFILES_PR_FTPPASV) {
                ftp_pasv($this->handler, true);
            }

        } else {
            $this->loggedin = false;
        }
    }

    /**
     * Accessor method to return the host connection name
     *
     * @return string
     */
    public function get_connection_name() {
        return $this->config->name;
    }

    /**
     * Accessor method to check logged in status
     *
     * @return boolean
     */
    public function is_logged_in() {
        return $this->loggedin;
    }

    /**
     * Log an action. At present this uses mtrace but conceivably it could
     * extended to write to a log file.
     *
     * @param string $message  log message
     * @return void
     */
    protected function _log($message) {
        $timestamp = date("Ymd-Hi");
        mtrace("$timestamp: $message");
    }
    
    /**
     * Perform the file transfer
     *
     * @return integer  number of files transferred
     */
    public function transfer() {
        $count = 0;

        if ($this->loggedin) {

            switch ($this->config->operation) {
                case RFILES_OP_PUSH:
                    $count = $this->transfer_push();
                    break;
                case RFILES_OP_PULL:
                    $count = $this->transfer_pull();
                    break;
                default:
            }
        }
        return $count;
    }


    /**
     * Transfer the given list of files to remote server
     *
     * @return integer  number of files transferred
     */
    private function transfer_push() {
        $count = 0;
        if ($this->loggedin) {
            $transferfiles = $this->list_transfer_files();
            foreach ($transferfiles as $file) {
                $localfile = $this->config->localdirectory.'/'.$file;
                $remotefile = $this->config->remotedirectory.'/'.$file;
                if ($this->transfer_push_file($localfile, $remotefile)) {
                    $this->post_process_source_file($file);
                    $count++;
                }
            }
        }
        return $count;       
    }

    /**
     * Transfer a single file to remote server
     *
     * @param string $source  full pathname of file source
     * @param string $destination  full pathname of file destination
     * @return boolean  success?
     */
    protected function transfer_push_file($source, $destination) {
        return @ftp_put($this->handler, $destination, $source, FTP_BINARY);
    }

    /**
     * Transfer the given list of files to local server
     *
     * @return integer  number of files transferred
     */
    private function transfer_pull() {
        $count = 0;
        if ($this->loggedin) {
            $transferfiles = $this->list_transfer_files();
            foreach ($transferfiles as $file) {
                $this->_log("Transfer: $file");
                $localfile = $this->config->localdirectory.'/'.$file;
                $remotefile = $this->config->remotedirectory.'/'.$file;
                if ($this->transfer_pull_file($remotefile, $localfile)) {
                    $this->post_process_source_file($file);
                    $count++;
                }
            }
        }
        return $count;       
    }

    /**
     * Transfer a single file from remote server
     *
     * @param string $source  full pathname of file source
     * @param string $destination  full pathname of file destination
     * @return boolean  success?
     */
    protected function transfer_pull_file($source, $destination) {
        $size = @ftp_size($this->handler, $source);
        if ($size != -1) {
            $this->_log("Pull File: $source: $destination");
            return @ftp_get($this->handler, $destination, $source, FTP_BINARY);
        } else {
            return false;
        }
    }

    /**
     * Post process a source file
     *
     * @param string $file  the processed file, not including the path
     * @return boolean
     */
    private function post_process_source_file($file) {
        $destination = $this->config->sourceprocessdirectory.'/'.$file;

        switch ($this->config->sourcefileaction) {
            case RFILES_SRC_MOVE:
                if ($this->config->operation == RFILES_OP_PUSH) {
                    $success = $this->move_local_file($this->config->localdirectory.'/'.$file, $destination);
                } elseif ($this->config->operation == RFILES_OP_PULL) {
                    $success = $this->move_remote_file($this->config->remotedirectory.'/'.$file, $destination);
                }
                break;

            case RFILES_SRC_DELETE:
                if ($this->config->operation == RFILES_OP_PUSH) {
                    $success = delete_local_file($orig);
                } elseif ($this->config->operation == RFILES_OP_PULL) {
                    $success = delete_remote_file($orig);
                }
                break;

            case RFILES_SRC_LEAVE:
            default:
                $success = true;
        }

        return $success;
    }

    /**
     * Move a local file
     *
     * @param string $source  full pathname to source
     * @param string $destination  full pathname to destination
     * @return boolean  success?
     */
    private function move_local_file($source, $destination) {
        $this->_log("Moving Local File: $source: $destination");
        return rename($source, $destination);
    }

    /*
     * Delete a local file
     *
     * @param string $source  full pathname to source
     * @return boolean  success?
     */
    private function delete_local_file($source) {
        return unlink($source);
    }

    /**
     * Move a remote file
     *
     * @param string $source  full pathname to source
     * @param string $destination  full pathname to destination
     * @return boolean  success?
     */
    protected function move_remote_file($source, $destination) {
        $this->_log("Moving Remote File: $source: $destination");
        return ftp_rename($this->handler, $source, $destination);
    }

    /**
     * Delete a remote file
     *
     * @param string $source  full pathname to source
     * @return boolean  success?
     */
    protected function delete_remote_file($source) {
        return @ftp_delete($this->handler, $source);
    }

    /**
     * Return a list of files to be transferred
     *
     * @return array  list of files
     */
    private function list_transfer_files() {

        switch ($this->config->operation) {
            case RFILES_OP_PUSH:
                $sourcefiles  = $this->get_local_files();
                $destfiles    = $this->get_remote_files();
                break;
            case RFILES_OP_PULL:
                $sourcefiles  = $this->get_remote_files();
                $destfiles    = $this->get_local_files();
                break;
            default:
                $sourcefiles = array();
                $destfiles   = array();
        }

        $transferfiles = array();
        foreach ($sourcefiles as $file) {
            $file = basename($file);
            switch ($this->config->existingfiles) {
                case RFILES_EF_SOURCE:
                    $transferfiles[] = $file;
                case RFILES_EF_LATEST:
                    /// TODO
                case RFILES_EF_DESTINATION:
                    if (!in_array($file, $destfiles)) {
                        $transferfiles[] = $file;
                    }
                default:
            }
        }

        return $transferfiles;
    }

    /**
     * Get a list of local files that match the file pattern
     *
     * @uses $this->config->pattern
     * @return array  a list of files
     */
    private function get_local_files() {
        $files = array();
        $pattern = (empty($this->config->pattern)) ? '' : '/'.$this->config->pattern.'/';

        if ($dh = @opendir($this->config->localdirectory)) {
            while (($file = readdir($dh)) !== false) {
                
                if (is_dir($file)) continue;

                // Check file pattern
                if (empty($pattern)) {
                    $files[] = $file;
                } else if (preg_match($pattern, $file, $matches)) {
                    $files[] = $file;
                }
            }
            closedir($dh);
        }
        return array_unique($files);
    }

    /**
     * Get a list of remote files that match the file pattern
     *
     * @uses $this->config->pattern
     * @return array  a list of files
     */
    protected function get_remote_files() {
        $files = array();
        $pattern = (empty($this->config->pattern)) ? '' : '/'.$this->config->pattern.'/';

        if ($this->loggedin) {
            if ($filelist = ftp_nlist($this->handler, $this->config->remotedirectory)) {
                foreach ($filelist as $file) {
                    
                    // TODO check if the file is a directory

                    // Check the file pattern
                    if (empty($pattern)) {
                        $files[] = $file;
                    } else if (preg_match($pattern, $file, $matches)) {
                        $files[] = $file;
                    }
                }
            }
        }
        return array_unique($files);
    }

}


// TODO Move the above into separate classes

class rfiles_host_ftp extends rfiles_host {
}

class rfiles_host_ftppasv extends rfiles_host_ftp {
}

class rfiles_host_ftpssl extends rfiles_host_ftp {
}

class rfiles_host_sftp extends rfiles_host {
}
