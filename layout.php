<!DOCTYPE html>
<html lang="en" data-token="<?php h($_SESSION["csrf"]) ?>">

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>WakeOnLan</title>

<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<!--<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">-->
<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="asset/style.css">
<script src="script/app.js"></script>


<header class="navbar navbar-static-top navbar-inverse" id="top">
  <div class="container">
    <div class="navbar-header">
      <button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".bs-navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a href="?" class="navbar-brand">WakeOnLan</a>
    </div>
    <nav class="collapse navbar-collapse bs-navbar-collapse">
      <ul class="nav navbar-nav">
        <li<?php if($page->layoutName==="index") echo ' class="active"'; ?>><a href="?">DashBord</a>
        <li<?php if($page->layoutName==="add") echo ' class="active"'; ?>><a href="?m=add">Add</a>
        <li<?php if($page->layoutName==="setting") echo ' class="active"'; ?>><a href="?m=setting">Setting</a>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li><a href="?m=logout">Logout</a>
        <li><a href="" class="all-refresh">Refresh (5)</a>
      </ul>
    </nav>
  </div>
</header>


<div class="container">
  <h1><?php h($page->title); ?></h1>
  <hr>
  
<!--  <pre>
    <?php  ?>
  </pre>-->
  
  <div class="page-<?php h($page->layoutName) ?>">
<?php
extract($page->data);
switch ($page->layoutName){
  
  
  case "login": ?>
    <div class="row">
      <div class="col-sm-6 col-sm-offset-3">
        <div class="panel panel-default">
          <div class="panel-body">
            <?php if($error){ ?>
            <div class="alert alert-danger"><?php h($error) ?></div>
            <?php } ?>
            <form class="form" role="form" method="post">
              <div class="form-group">
                <input type="password" name="pass" class="form-control" id="inputPassword" placeholder="Password">
                <input type="hidden" name="csrf" value="<?php h($_SESSION["csrf"]) ?>">
              </div>
              <button type="submit" class="btn btn-primary btn-block">Login</button>
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="remember" value="remember" checked="checked"> Remember
                </label>
              </div>
            </form>
            <?php if(false){ ?>
            <hr>
            <button type="submit" class="btn btn-default btn-block">Login with Twitter</button>
            <?php } ?>
            <hr>
            <p>Default Password : password
          </div>
        </div>
      </div>
    </div>
    <?php break; // End Login
  
  
  case "index": ?>
    <?php if(checkPass("password",$setting->password)){ ?>
      <div class="alert alert-danger">Please change password.</div>
    <?php } ?>
      
    <div class="row">
    <?php foreach($deviceList as $v){ /* @var $v device */ ?>
      <div class="col-sm-6">
        <div class="panel panel-default device success" data-deviceid="<?php h($v->id) ?>">
          <div class="panel-body">
            <h4><?php h($v->name) ?></h4>
            <p>
              <button type="button" class="btn btn-xs refresh btn-primary success">OK</button>
              <button type="button" class="btn btn-xs refresh btn-info loading disabled">Loading</button>
              <button type="button" class="btn btn-xs refresh btn-danger fail">NG</button>
              <?php h($v->ip) ?> / <span class="response-time"></span>
            <div class="progress progress-striped">
              <i class="progress-bar" style="width: 0%"></i>
            </div>
            <ul class="nav nav-pills nav-justified">
              <li><a href="" class="do">Wake / Sleep</a>
              <li><a href="?m=add&id=<?php h($v->id) ?>">Edit</a>
            </ul>
          </div>
        </div>
      </div>
    <?php } ?>
    </div>
    <?php break; // End Index
    
  case "add": ?>
    <div class="row">
      <div class="col-sm-4">
        <ul class="list-group arp-result">
        </ul>
        <button class="btn btn-default btn-block list-refresh">Network Search</button>
      </div>
      <div class="col-sm-8">
        <?php if($error){ ?>
        <div class="alert alert-danger"><?php echo implode("<br>\n", $error) ?></div>
        <?php } ?>
        <?php if($message){ ?>
        <div class="alert alert-success"><?php h($message) ?></div>
        <?php } ?>
        <form class="form-horizontal" method="post">
          <div class="form-group">
            <label for="inputName" class="col-sm-2 control-label">Name</label>
            <div class="col-sm-10">
              <input type="text" name="name" class="form-control" id="inputName" placeholder="Rooter" value="<?php h($device?$device->name:$_POST["name"]) ?>" tabindex="1">
            </div>
          </div>
          <div class="form-group">
            <label for="inputIp" class="col-sm-2 control-label">IP</label>
            <div class="col-sm-10">
              <input type="text" name="ip" class="form-control" id="inputIp" placeholder="192.168.11.1 or localhost" value="<?php h($device?$device->ip:$_POST["ip"]) ?>" tabindex="2">
            </div>
          </div>
          <div class="form-group">
            <label for="inputMac" class="col-sm-2 control-label">MAC</label>
            <div class="col-sm-10">
              <input type="text" name="mac" class="form-control" id="inputMac" placeholder="32:61:3C:4E:B6:05" value="<?php h($device?$device->mac:$_POST["mac"]) ?>" tabindex="3">
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-default" tabindex="4">Submit!</button>
              <input type="hidden" name="csrf" value="<?php h($_SESSION["csrf"]) ?>">
            </div>
          </div>
        </form>
      </div>
    </div>
    <?php break; // End Add
    
  
  case "setting": ?>
    <div class="row">
      <div class="col-sm-8 col-sm-offset-2">
        <?php if($message){ ?>
        <div class="alert alert-success"><?php echo implode("<br>\n", $message) ?></div>
        <?php } ?>
        <form class="form-horizontal" method="post">
          <div class="form-group">
            <label for="inputName" class="col-sm-2 control-label">NewPassword</label>
            <div class="col-sm-10">
              <input type="password" name="pass" class="form-control" id="inputName">
              <p class="help-block">If you want to change password</p>
            </div>
          </div>
          <div class="form-group">
            <label for="inputIp" class="col-sm-2 control-label">PingTimeOut</label>
            <div class="col-sm-10">
              <input type="number" name="ping" class="form-control" id="inputIp" value="<?php h($setting->pingTimeout) ?>">
              <p class="help-block">Default 5 second</p>
            </div>
          </div>
          <div class="form-group">
            <label for="inputRefresh" class="col-sm-2 control-label">RefreshTime</label>
            <div class="col-sm-10">
              <input type="number" name="refresh" class="form-control" id="inputRefresh" value="<?php h($setting->refreshInterval) ?>">
              <p class="help-block">Default 20 second</p>
            </div>
          </div>
<!--          <div class="form-group">
            <label class="col-sm-2 control-label">Twitter</label>
            <div class="col-sm-10">
              <button class="btn btn-info">Conection</button>
              <p class="help-block">User @screenname - numid</p>
              <input type="checkbox"> Login From Twitter Onry
            </div>
            <div class="col-sm-5">
            </div>
          </div>-->
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-default">Submit!</button>
              <input type="hidden" name="csrf" value="<?php h($_SESSION["csrf"]) ?>">
            </div>
          </div>
        </form>
      </div>
    </div>
    <?php break; // End Setting
    
  default : ?>
    <h1>404 NotFound</h1>
    <?php break; // End Default
} ?>
  </div>
</div>


<footer>
  <div class="container text-center">
    <hr>
    WakeOnLan by <a href="https://github.com/kamijin-fanta" target="new">kamijin_fanta</a>
  </div>
</footer>