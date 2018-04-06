<?php
include_once("class.ilSEBPlugin.php");
include_once("class.ilSEBConfig.php");

class ilSEBAccessChecker {
    private $conf;
    private $DIC;
    
    private $is_seb;
    
    public function isSeb() {
        return $this->is_seb;
    }
    
    private $needs_seb;
    
    public function getNeedsSeb() {
        return $this->needs_seb;
    }
    
    private $switch_to_seb_skin;
    
    public function getSwitchToSebSkin() {
        return $this->switch_to_seb_skin;
    }
    
    private $is_anonymus_user;
    
    public function isAnonymusUser() {
        return $this->is_anonymus_user;
    }
    
    public function checkForValidSebKey() {
        $server_req_header = $_SERVER[ilSEBPlugin::REQ_HEADER];
    }
    
    public function __construct($ref_id) {
        global $DIC;
        $this->DIC = $DIC;
        $user = $this->DIC->user();
        $rbacreview = $this->DIC->rbac()->review();
        $this->conf = ilSEBConfig::getInstance();
        
        $this->is_seb = $this->detectSeb($ref_id);
        
        $is_root = $rbacreview->isAssigned($user->id,SYSTEM_ROLE_ID);
        
        $this->is_anonymus_user = ($user->id == ANONYMOUS_USER_ID);
        
        $is_logged_in = ($user->id && !$this->is_anonymus_user);
        
        $role_deny = (int) $this->conf->getRoleDeny();
        
        // check role deny
        if ($is_logged_in && $role_deny && !$is_root) {
            // check access
            $this->needs_seb = ($role_deny == 1 || $rbacreview->isAssigned($user->id,$role_deny));
        } else {
            $this->needs_seb = false;
        }
                
        // check if a switch to the seb skin is needed
        $is_kiosk_user = (($this->conf->getRoleKiosk() == 1 || $rbacreview->isAssigned($user->id,$this->conf->getRoleKiosk())) && !$is_root);
        
        if ($is_logged_in && $is_kiosk_user) {
            $this->switch_to_seb_skin = true;
        } else {
            $this->switch_to_seb_skin = false;
        }
    }
    
    public function exitIlias() {
        $pl = ilSEBPlugin::getInstance();
        ilSession::setClosingContext(ilSession::SESSION_CLOSE_LOGIN);
        
        if (is_object($this->DIC['ilAuthSession']->isValid())) {
            $this->DIC['ilAuthSession']->logout();
        }
        
        session_unset();
        session_destroy();
        $script = "login.php?target=".$_GET["target"]."&client_id=".$_COOKIE["ilClientId"];
        $headerTxt = $pl->txt("forbidden_header");
        $msgTxt = $pl->txt("forbidden_message");
        $loginTxt = $pl->txt("forbidden_login");
        $login = "<a href=\"" . $script . "\">" . $loginTxt . "</a>";
        $msg = file_get_contents("./Customizing/global/skin/seb/tpl.seb_forbidden.html");
        $msg = str_replace("{TXT_HEADER}", $headerTxt, $msg);
        $msg = str_replace("{TXT_MESSAGE}", $msgTxt, $msg);
        $msg = str_replace("{LOGIN}", $login, $msg);
        
        header('HTTP/1.1 403 Forbidden');
        echo $msg;
        exit;
    }
    
    private function detectSeb($ref_id = null, $check_all_obj_keys = false) {
        global $ilDB;
        
        $server_req_header = $_SERVER[ilSEBPlugin::REQ_HEADER];
        
        // ILIAS want to detect a valid SEB with a custom req_header and seb_key
        // if no req_header exists in  the current request: not a seb request
        if (!$server_req_header || $server_req_header == "") {
            return ilSebPlugin::NOT_A_SEB_REQUEST; // not a seb request
        } else if ($this->conf->checkSebKey($server_req_header, $this->getFullUrl())) {
            return ilSEBPlugin::SEB_REQUEST;
        } else if ($check_all_obj_keys && $this->conf->checkKeyAgainstAllObjectKeys($server_req_header, $this->getFullUrl())) {
            return ilSEBPlugin::SEB_REQUEST_OBJECT_KEYS_UNSPECIFIC;
        } else if ($this->conf->checkObjectKey($server_req_header, $this->getFullUrl(), $ref_id)) {
            return ilSEBPlugin::SEB_REQUEST_OBJECT_KEYS;
        } else {
            return ilSebPlugin::NOT_A_SEB_REQUEST; // not a seb request
        }
    }
    
    private function getFullUrl() {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
        $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") ? "" : (":".$_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }
}