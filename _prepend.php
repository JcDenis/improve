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

$d = dirname(__FILE__) . '/inc/';
$__autoload['Improve']                = $d . 'class.improve.php';
$__autoload['ImproveAction']          = $d . 'class.improve.action.php';

$__autoload['ImproveActionDcstore']   = $d . 'lib.improve.action.dcstore.php';
$__autoload['ImproveActionEndoffile'] = $d . 'lib.improve.action.php';
$__autoload['ImproveActionGitshields'] = $d . 'lib.improve.action.gitshields.php';
$__autoload['ImproveActionLicensefile'] = $d . 'lib.improve.action.licensefile.php';
$__autoload['ImproveActionNewline']   = $d . 'lib.improve.action.php';
$__autoload['ImproveActionPhpheader'] = $d . 'lib.improve.action.phpheader.php';
$__autoload['ImproveActionTab']       = $d . 'lib.improve.action.php';
$__autoload['ImproveActionZip']       = $d . 'lib.improve.action.zip.php';
$__autoload['ImproveZipFileZip']      = $d . 'lib.improve.action.zip.php';