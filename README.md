moodle-local_rfiles
===================

Manage remote file transfers via moodle.

Configure FTP connections which push/pull files from/to a local folder. File transfer frequency is configurable.

Supports the following protocols: FTP, FTP (PASV), FTP (SSL)


CODE SOURCE
===========

https://github.com/PukunuiAustralia/moodle-local_rfiles


CONTRIBUTORS
============

Shane Elliott, shane@pukunui.com


INSTALLATION
============

A. Using Git
  1. Clone repository into /local/rfiles (within your moodle folder)
  2. Check the appropriate version to match your moodle code
  3. Visit the moodle notifications page for installation
  4. Click the "Remote Files" link in the site amdinsitration to configure connections

B. Downloading ZIP archive
  1. Get the appropriate ZIP archive to match your moodle code
  2. Unzip folder and rename to /local/rfiles
  3. Visit the moodle notifications page for installation
  4. Click the "Remote Files" link in the site amdinsitration to configure connections


TODO
====

- Better classes for extensibility
- Support for SFTP
- Support for rsync
