<?php
session_start();
ini_set('display_errors', 1 );
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

define("DEVICE_SETTING_DIR", dirname(__FILE__) . "/setting/device/");
define("COMMON_SETTING_FILE", dirname(__FILE__) . "/setting/common.json");

$page = new page();
$setting = new setting();

if(empty($_SESSION["csrf"])){
  $_SESSION["csrf"] = randumStr();
}

if(empty($_SESSION["login"])){
  if(isset($_COOKIE["wolrem"])&&$_COOKIE["wolrem"]===$setting->remember){
    $_SESSION["login"] = true;
    header("Location: ".$_SERVER["REQUEST_URI"]); exit;
  }
  if(isset($_POST["csrf"])&&$_SESSION["csrf"]===$_POST["csrf"]){
    if(checkPass($_POST["pass"], $setting->password)){ // OK!
      if($_POST["remember"]){
        setcookie("wolrem", $setting->remember, time()+3600*24*14);
      }
      $page->data["error"] = print_r($_POST, true);
      $_SESSION["login"] = true;
      header("Location: ".$_SERVER["REQUEST_URI"]); exit;
    } else {
      $page->data["error"] = "Filed";
    }
  }
  $_SESSION["csrf"] = randumStr();
  $page->title = "Login";
  $page->layoutName = "login";
} else {
  switch (isset($_GET["m"])?$_GET["m"]:null){
    case null:
      $page->title = "DashBord";
      $page->layoutName = "index";
      $page->data["deviceList"] = getDeviceList();
      break;
    case "add":
      $error = false;
      if(empty($_GET["id"])){
        $page->title = "DeviceAdd";
      } else {
        $page->title = "DeviceEdit";
        $d = new device($_GET["id"]);
        $page->data["device"] = $d;
        if($d->id!==$_GET["id"]){
          $page->data["message"] = "Device Not Found";
          break;
        }
      }
      $page->layoutName = "add";
      
      if(isset($_POST["csrf"])&&$_SESSION["csrf"]===$_POST["csrf"]){
        $page->data["error"] = [];
        $mac = preg_replace("@[^0-9a-fA-F]@", "", $_POST["mac"]);
        if(strlen($mac)!==12){
          $page->data["error"][] = "MAC Address Format Error";
        }
        
        if(!count($page->data["error"])){
          $newDevice = new device($d->id);
          $newDevice->name = $_POST["name"];
          $newDevice->ip = $_POST["ip"];
          $newDevice->mac = strtoupper($mac);
          $newDevice->save();
          $page->data["message"] = "Success!";
          $page->data["device"] = $newDevice;
        }
      }
      break;
    case "setting":
      $page->title = "WolSetting";
      $page->layoutName = "setting";
      if(isset($_POST["csrf"])&&$_SESSION["csrf"]===$_POST["csrf"]){
        if(!empty($_POST["pass"])){
          $setting->password = passHash($_POST["pass"]);
          $page->data["message"][] = "Set New Password";
        }
        if(!empty($_POST["ping"])){
          $setting->pingTimeout = preg_replace("@[\D]@", "", $_POST["ping"]);
          if($setting->pingTimeout<1) $setting->pingTimeout = 1;
          if($setting->pingTimeout>10)$setting->pingTimeout = 10;
          $page->data["message"][] = "Set New PingTimeout";
        }
        if(!empty($_POST["refresh"])){
          $setting->refreshInterval = preg_replace("@[\D]@", "", $_POST["refresh"]);
          if($setting->refreshInterval<5) $setting->refreshInterval = 5;
          if($setting->refreshInterval>3600)$setting->refreshInterval = 3600;
          $page->data["message"][] = "Set New PingTimeout";
        }
        
        
        $page->data["message"][] = "Success!";
      }
      break;
    case "ping":
      $page->layoutName = "json";
      $id = preg_replace("[\W]", "", $_GET["id"]);
      $status = new deviceStatus();
      if(isset($_POST["csrf"])&&$_SESSION["csrf"]===$_POST["csrf"]){
        session_write_close(); // befor heavy process
        $status->id = $id;
        if($device=getDevice($id)){
          $pingTime = 0;
          $pignResult = ping($device->ip, $pingTime);
          $status->status = $pignResult?2:1;
          $status->responseTime = $pingTime;
          
        } else {
          $status->status = 0;
          $status->responseTime = 0;
        }
      } else {
        $status->status = 0;
      }
      $page->data = $status;
      break;
    case "do":
      $page->layoutName = "json";
      $id = preg_replace("[\W]", "", $_GET["id"]);
      $method = preg_replace("[\W]", "", $_GET["method"]);
      
      $status = new deviceStatus();
      $status->responseTime = 0;
      if(isset($_POST["csrf"])&&$_SESSION["csrf"]===$_POST["csrf"]){
        session_write_close(); // befor heavy process
        $status->id = $id;
        if($device=getDevice($id)){
          $pignResult = wol($device->mac);
          $status->status = $pignResult?2:1;
          
        } else {
          $status->status = 0;
        }
      } else {
        $status->status = 0;
      }
      $page->data = $status;
//      
//      usleep(400 * 1000);
//      $page->data = ["id"=>$id];
      break;
    case "getlist":
      $page->layoutName = "json";
      $id = preg_replace("[\W]", "", $_GET["id"]);
      $result = [];
      if(isset($_POST["csrf"])&&$_SESSION["csrf"]===$_POST["csrf"]){
        session_write_close(); // befor heavy process
        $i=5;
        while(--$i){
          $d = new arpResult();
          $d->ip = "192.168.11.".$i;
          $d->mac = "00:11:22:33:44:55:66:77";
          $result[] = $d;
        }
        $result = scanLanDevice();
        // Content-Type
        usleep(rand(500, 1500) * 1000);
      }
      $page->data = $result;
      break;
    case "logout":
      unset($_SESSION["login"]);
      $pos = strpos($_SERVER["REQUEST_URI"], "?");
      setcookie("wolrem", "erase", 0);
      header("Location: ".substr($_SERVER["REQUEST_URI"], 0, $pos)); exit;
      break;
  }
}


// output
$setting->save();
if($page->layoutName==="json"){
  header("Content-Type: application/json");
  echo json_encode($page->data);
} else {
  include './layout.php';
}

// lib
function getDeviceList(){
  $result = [];
  
  $list = scandir(DEVICE_SETTING_DIR);
  $fileList = [];
  foreach ($list as $v){
    if(is_file(DEVICE_SETTING_DIR.$v)){
      $fileList[] = str_replace(".json", "", $v);
    }
  }
  
  foreach ($fileList as $v){
    $result[] = new device($v);
  }
  return $result;
}
function getDevice($id){
  foreach(getDeviceList() as $v){
    if($v->id!==$id) continue;
    else {
      /* @var $v device */
      return $v;
    }
  }
  return false;
}
function h($str){
  echo htmlspecialchars($str);
}
function randumStr(){
  $rand_str = "";
  $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
  for($i=0;$i<50; $i++){
    $rand_str .= substr($str, mt_rand(0, strlen($str)-1), 1);
  }
  return hash('sha256', $rand_str);
}

function passHash($password, $hash = null){
  if(empty($hash)) $hash = randumStr ();
  return hash('sha256', $password.$hash)."/".$hash;
}
function checkPass($input, $hashedPass){
  $array = explode("/", $hashedPass);
  return passHash($input, $array[1]) === $hashedPass;
}

function scanLanDevice(){
  // ping -b -c 2 255.255.255.255
  // arp -a
  exec("ping -b -c 2 255.255.255.255");
  $res = [];
  exec ("/usr/sbin/arp -a",$res);
  $result = [];
  foreach($res as $i => $v){
    if($i===0)    continue;
    $match = [];
    preg_match('@^(?P<name>\S*)(.*)\((?P<ip>.*)\)(.*)(?P<mac>([\da-zA-Z]{2}:){5}[\da-zA-Z]{2})@', $v, $match);
    if($match["ip"]){
      $device = new arpResult();
      $device->ip = $match["name"]!=="?"?$match["name"]:$match["ip"];
      $device->mac = $match["mac"];
      $result[] = $device;
    }
  }
  return $result;
}

function ping($host, &$time = null) {
// For Linux
  $r = exec(sprintf('ping -c 1 -W 1 %s', escapeshellarg($host)), $res, $rval);
//print_r($r);
  $matches = array();
  preg_match('@time=([0-9.]+\s*ms)@', implode("", $res), $matches);
  $time = isset($matches[1]) ? preg_replace('@[^0-9.ms]@', "", $matches[1]) : "";
  return $rval === 0;
}

function wol($mac) {
  $body = h2s('FFFFFFFFFFFF');
  for ($i = 0; $i < 20; $i++) {
    $body .= h2s($mac);
  }
  $soc = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
  socket_set_option($soc, SOL_SOCKET, SO_BROADCAST, 1);
  socket_connect($soc, "255.255.255.255", 2304);
  socket_write($soc, $body, 126);
  socket_close($soc);
}

function h2s($h) {
  $s = '';
  $p = 0;
  while ($p < strlen($h)) {
    $s .= chr(intval(substr($h, $p, 2), 16));
    $p += 2;
  }
  return $s;
}



class page {
  public $title;
  public $layoutName;
  public $data = [];
}
class arpResult {
  public $ip;
  public $mac;
}
class device {
  public $dirPath;
  public $id;
  public $name;
  public $ip;
  public $mac;
  public $key;
  public $chedule;
  public $message;
  public $update;
  

  public function __construct($deviceId = null) {
    $this->dirPath = DEVICE_SETTING_DIR;

    if ($deviceId && file_exists($this->getFilePath($deviceId))) {
      $data = json_decode(file_get_contents($this->getFilePath($deviceId)), true);
      foreach ($data as $i => $v) {
        $this->{$i} = $v;
      }
    }
    if(empty($this->update)){
      do{
        $deviceId = randumStr();
      } while (file_exists($this->getFilePath($deviceId)));
    }
    $this->id = $deviceId;
  }

  public function getFilePath($id){
    return $this->dirPath . $id . ".json";
  }
  public function save(){
    $setting = [];
    $this->update = time();
    foreach ($this as $i => $v) {
        $setting[$i] = $v;
    }
    unset($setting["dirPath"]);
    $data = compact($this->password, $this->remember);
    file_put_contents($this->getFilePath($this->id), json_encode($setting));
  }
  
}
class deviceStatus {
  public $id;
  public $status; // 0:OtherError 1:Timeout 2:Successe
  public $responseTime;
}

class setting {
  public $settingFilePath;
  public $password;
  public $remember;
  public $pingTimeout;
  public $refreshInterval;
  
  public function __construct() {
    $this->settingFilePath = COMMON_SETTING_FILE;
    // set defuaut "password"
    $this->password = "c7db2366bfc1ffd7a5794dd7de0932eb7d6db1063779c515d713198a9d218d06/caf4ed68075e40a4dbdd8ad2340f925ba0710b81fe294bf65422b5012a11061f";
    if(file_exists($this->settingFilePath)){
      $data = json_decode (file_get_contents ($this->settingFilePath), true);
      foreach ($data as $i => $v) {
          $this->{$i} = $v;
      }
    }
    if(empty($this->remember)){
      $this->remember = randumStr();
    }
    if(empty($this->pingTimeout)){
      $this->pingTimeout = 5;
    }
    if(empty($this->refreshInterval)){
      $this->refreshInterval = 20;
    }
  }
  
  public function save(){
    $setting = [];
    foreach ($this as $i => $v) {
        $setting[$i] = $v;
    }
    unset($setting["settingFilePath"]);
    $data = compact($this->password, $this->remember);
    file_put_contents($this->settingFilePath, json_encode($setting));
  }
}



