<?php
/**
 * @brief improve, a plugin for Dotclear 2
 * 
 * @package Dotclear
 * @subpackage Plugin
 * 
 * @author Jean-Christian Denis and contributors
 * 
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('DC_RC_PATH')) {
    return;
}

$improve_libs = [
    'Improve'                   => 'class.improve.php',
    'ImproveAction'             => 'class.improve.action.php',

    'ImproveActionDcdeprecated' => 'lib.improve.action.dcdeprecated.php',
    'ImproveActionDcstore'      => 'lib.improve.action.dcstore.php',
    'ImproveActionEndoffile'    => 'lib.improve.action.php',
    'ImproveActionGitshields'   => 'lib.improve.action.gitshields.php',
    'ImproveActionLicensefile'  => 'lib.improve.action.licensefile.php',
    'ImproveActionNewline'      => 'lib.improve.action.php',
    'ImproveActionPhpheader'    => 'lib.improve.action.phpheader.php',
    'ImproveActionTab'          => 'lib.improve.action.php',
    'ImproveActionZip'          => 'lib.improve.action.zip.php',
    'ImproveZipFileZip'         => 'lib.improve.action.zip.php'
];
foreach($improve_libs as $class => $file) {
    $__autoload[$class] = dirname(__FILE__) . '/inc/' . $file;
}