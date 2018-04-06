<?php

include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");
 
/**
 * Example user interface plugin
 *
 * @author Stefan Schneider <schneider@hrz.uni-marburg.de>
 * @version $Id$
 *
 */
class ilSEBPlugin extends ilUserInterfaceHookPlugin
{
	const NOT_A_SEB_REQUEST = 0;
	const SEB_REQUEST = 1;
	const SEB_REQUEST_OBJECT_KEYS = 2;
	const SEB_REQUEST_OBJECT_KEYS_UNSPECIFIC = 3;
	const ROLES_NONE = 0;
	const ROLES_ALL = 1;
	const BROWSER_KIOSK_ALL = 0;
	const BROWSER_KIOSK_SEB = 1;
	const CACHE = 'SEB_CONFIG_CACHE';
	const REQ_HEADER = 'HTTP_X_SAFEEXAMBROWSER_REQUESTHASH';
	
	private static $instance;
	
	public static function getInstance() {
	    if (isset(self::$instance)) {
	        return self::$instance;
	    } else {
	        self::$instance = new self;
	        return self::$instance;
	    }
	}
	
	private static function _isAPCInstalled() {
		return (function_exists("apc_store") && function_exists("apc_fetch"));
	}
	
	public static function _flushAPC() {
		if (ilSEBPlugin::_isAPCInstalled() && apc_exists(ilSEBPlugin::CACHE))  {
			apc_delete(ilSEBPlugin::CACHE);
		}
	}
	
	function getPluginName() {
		return "SEB";
	}
}

?>
