<?php
/**
 * The AuthNet PHP SDK. Include this file in your project.
 *
 * @package AuthNet
 */
require dirname(__FILE__) . '/lib/shared/AuthNet_Request.php';
require dirname(__FILE__) . '/lib/shared/AuthNet_Response.php';
require dirname(__FILE__) . '/lib/AuthNet.php';

/**
 * Exception class for AuthNet PHP SDK.
 *
 * @package AuthNet
 */
class AuthNet_Exception extends Exception {

}