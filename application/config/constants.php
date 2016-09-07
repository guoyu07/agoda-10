<?php

//Global config.
/*
  |--------------------------------------------------------------------------
  | Global config file
  |--------------------------------------------------------------------------
  |
  | These used for tell server when was use compress xml data and very useful
  | for idents development url or live url.
  |
 */
require realpath(BASEPATH . '../../globalConfig.php');

/*
  |--------------------------------------------------------------------------
  | Supplier configurations.
  |--------------------------------------------------------------------------
  |
  | These prefs and used when identifer supplier came from.
  | Supplier code (CLxxx) used for constant value in Func.php for queries
  | some results in mapping database.
  |
 */
define('_SUPPLIERCODE', 'CL958');
define('_SUPPLIERNAME', 'AGODA');
define('PATH_PHPFunction', '../../../PHPFunction/');

/*
  |--------------------------------------------------------------------------
  | File and Directory Modes
  |--------------------------------------------------------------------------
  |
  | These prefs are used when checking and setting modes when working
  | with the file system.  The defaults are fine on servers with proper
  | security, but you may wish (or even need) to change the values in
  | certain environments (Apache running a separate process for each
  | user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
  | always be used to set the mode correctly.
  |
 */
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
  |--------------------------------------------------------------------------
  | File Stream Modes
  |--------------------------------------------------------------------------
  |
  | These modes are used when working with fopen()/popen()
  |
 */

define('FOPEN_READ', 'rb');
define('FOPEN_READ_WRITE', 'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE', 'ab');
define('FOPEN_READ_WRITE_CREATE', 'a+b');
define('FOPEN_WRITE_CREATE_STRICT', 'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');
