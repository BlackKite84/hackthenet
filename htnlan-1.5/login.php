<?php
define('IN_HTN',1);
include('gres.php');
if (file_exists("./data/newround.txt"))
{
	$new_round_time = file_get("./data/newround.txt");
}
else 
{
	$new_round_time = "1";
}
if(isadmin(trim($_REQUEST['nick'])) == "true" || time() > $new_round_time) { 
if (file_exists("./data/roundend.txt"))
{
	$stop_time = file_get("./data/roundend.txt");
}
else 
{
	$stop_time = "100000000000000000000000";
}
if(time() < $stop_time) { 
$ref=$_SERVER['HTTP_REFERER'];
$refd=parse_url($ref);
#echo $refd['host'].' '.$_SERVER['HTTP_HOST'];
if($refd['host']!="htn.mamboportal.at")
{
	if($ref!='' && $refd['host']!=$_SERVER['HTTP_HOST'] && $refd['host']!='ssl-id1.de' && count($_GET)<2) die('Rejected! Please don\'t use your own log in pages.');
}

$action=$_REQUEST['page'];
if($action=='') $action=$_REQUEST['mode'];
if($action=='') $action=$_REQUEST['action'];
if($action=='') $action=$_REQUEST['a'];
if($action=='') $action=$_REQUEST['m'];

if($action=='login') {

if((int)@file_get('data/calc-time.dat')<=time() || @file_get('data/calc-running.dat')=='yes') {
  $pwd=urlencode($_REQUEST['pwd']);
  $nick=urlencode($_REQUEST['nick']);
  $stat=@file_get('data/calc-stat.dat');
  $chars = $_POST['chars'];
  echo '<html>
<head>
<title>Sorry</title></head><body style="font-family:arial;text-align:center;vertical-align:middle;">
<h1>Sorry</h1>
<b>Der Server ist im Moment mit der Kalkulation der Punktest&auml;nde besch&auml;ftigt!</b>
<br /><br />Aktueller Status: <tt>'.$stat.'</tt>
</body></html>';
if(@file_get('data/calc-running.dat')=='') include('calc_points.php');
exit;
} else {
    $ts=time();
    if($ts%2!=0) {
      $verz='data/login';
      $h=opendir($verz);
      while($fn=readdir($h))
      {
        if(is_file($verz.'/'.$fn)) {
          if(fileatime($verz.'/'.$fn)+30*60 <= $ts) @unlink($verz.'/'.$fn);
        }
      }
      closedir($h);
    }
}

$pwd=trim($_REQUEST['pwd']);
$postpwd=$pwd;
$usrname=trim($_REQUEST['nick']);
$server=(int)$_REQUEST['server'];
if($server!=1 && $server!=2) exit;


$cookie=false;

if(substr_count(($_COOKIE['htnLoginData4']),'|')==2 && $_POST['save']=='yes') {
  list($serverdummy, $usrnamedummy, $pwd)=explode('|', $_COOKIE['htnLoginData4']);
  $cookie=true;
}
if($_POST['save']=='yes') {
  if($cookie==false)
    setcookie('htnLoginData4', $server.'|'.$usrname.'|'.md5($pwd), time()+10*365*24*60*60);
  else {
    #if()
    if($usrnamedummy!=$usrname || $postpwd!='[xpwd]') $pwd=md5($postpwd);
    #echo $postpwd;
    setcookie('htnLoginData4', $server.'|'.$usrname.'|'.$pwd, time()+10*365*24*60*60);
    #echo $server.'|'.$usrname.'|'.$pwd;
    $cookie=true;
  }
}
else
{
  setcookie('htnLoginData4');
}

mysql_select_db(dbname($server));


if($cookie!==true)
{
  $pwd=md5($pwd);
}

if(preg_match("/^[a-z0-9]+$/i", $pwd) == false || strlen($pwd) != 32 || preg_match("/(_|%)/", $pwd)) die('FUCK OFF!');

if($localhost)
{
  $r=db_query('SELECT * FROM users WHERE name LIKE \''.mysql_escape_string($usrname).'\' LIMIT 1;');
}
else
{
  $r=db_query('SELECT * FROM users WHERE name LIKE \''.mysql_escape_string($usrname).'\' AND password=\''.mysql_escape_string($pwd).'\' LIMIT 1;');
}


if(@mysql_num_rows($r)==1) {
  $usr=mysql_fetch_assoc($r);

/*  $onlineusers=getfilecount('data/login');
  if($usr['bigacc']!='yes' && $onlineusers>555) {
    simple_message('Leider ist die maximale Zahl von Spielern, die gleichzeitig online sein d&uuml;rfen, erreicht!
    <br />Bitte probier es in ein paar Minuten nochmal!','info');
    exit;
  } */
  
  $r = db_query('SELECT `chars` FROM `verify_imgs` WHERE `key`=\''.mysql_escape_string($_POST['key']).'\' LIMIT 1;');
  if(!$chars=@mysql_result($r,0,'chars'))
  {
    die('Fehler aufgetreten. Bitte Startseite aufrufen und per F5 oder Klick auf Aktualisieren neu laden. Dann nochmal probieren!');
  }
  if(strtoupper($chars) != strtoupper(trim($_POST['chars'])) && $usr['stat']<1000)
  {
    $sid = '';
    unset($usr);
    simple_message('Sicherungscode falsch abgetippt! Bitte nochmal probieren!');
    db_query('INSERT INTO logs SET type=\'badlogin\', payload=\'nick='.mysql_escape_string($_REQUEST['nick']).', chars='.mysql_escape_string($_REQUEST['chars']).'\';');
    exit;
  }
  
  if(count($_GET)<2 || $usr['bigacc']=='yes') {

  if($usr['locked']!='' && $usr['locked']!="no") {
    echo '<html><head><title>Sorry</title></head><body style="font-family:arial;text-align:center;vertical-align:middle;">
<br /><br /><br /><br />
<h1>Account gesperrt!</h1>
<b>Dieser Account wurde durch den Administrator des Spiels gesperrt!!</b>
<br /><br />Grund: '.$usr['locked'].'</body></html>';
    exit;
  }
  $sid=$server.create_sid();
  $sidfile='data/login/'.$usr['sid'].'.txt';
  if(file_exists($sidfile))
    @unlink($sidfile);
  elseif($usr['sid']!='')
    $notloggedout='&nlo=1';
  $usrid=$usr['id'];
  $pcid=mysql_result(db_query('SELECT id FROM pcs WHERE owner=\''.mysql_escape_string($usrid).'\' LIMIT 1'),'id');
  write_session_data();

  $ip=GetIP();
  $ip2=$ip['ip'];
  $ip=($ip['proxy']=='' ? $ip['ip'] : $ip['ip'].' over '.$ip['proxy']);
  if($_REQUEST['noipcheck']!='yes') {
    $noipcheck=$usr['noipcheck'];
  } else {
    $noipcheck='yes';
  }
  @db_query('INSERT INTO logins ( ip , usr_id , time ) VALUES ( \''.mysql_escape_string($ip2).'\', \''.mysql_escape_string($usrid).'\', \''.mysql_escape_string(time()).'\' );');
  db_query('UPDATE users SET sid=\''.mysql_escape_string($sid).'\', login_time=\''.mysql_escape_string(time()).'\', sid_ip=\''.mysql_escape_string($ip).'\', noipcheck=\''.mysql_escape_string($noipcheck).'\' WHERE id=\''.mysql_escape_string($usrid).'\' LIMIT 1;');
  if(is_noranKINGuser($usrid)===false) {
    $s='data/_server1/logins_'.strftime('%Y%m%d').'.txt';
    file_put($s,((int)@file_get($s))+1);
  }
  header('Location: game.php?m=start&sid='.$sid.$notloggedout);

  } else {
    simple_message('Die Funktion des Direkt-LogIns &uuml;ber die Adresszeile steht ab sofort nur noch ExtendedAccount-Usern zur verf&uuml;gung.<br />Bitte log dich &uuml;ber die Startseite ein!','info');
  }

} else {
  simple_message('Benutzername unbekannt oder Passwort falsch!');
  db_query('INSERT INTO logs SET type=\'badlogin\', payload=\'nick='.mysql_escape_string($_REQUEST['nick']).', pwd='.mysql_escape_string($_REQUEST['pwd']).'\';');
}

} elseif($action=='logout') { # ------------------------- LOG OUT ------------------------
$sid=$_REQUEST['sid'];
$fstr=@file_get('data/login/'.$sid.'.txt');
if($fstr!='') {
  $fstr=explode("\x0b",$fstr);
  $server=$fstr[0];
  mysql_select_db(dbname($server));
  db_query('UPDATE users SET sid=\'\' WHERE id=\''.mysql_escape_string($fstr[1]).'\'');
  unlink('data/login/'.$sid.'.txt');
}
if($_GET['redir']=='forum') header('Location: http://forum.hackthenet.org/'); else header('Location: pub.php');
}

/* if(!headers_sent()) no_(1); */
} else 
{ 
	simple_message("Die aktuelle Runde ist beendet!"); 
	exit;
}
} else { simple_message("Du kannst dich nicht einloggen. Der Server ist bis am ". date("d.m.Y",$new_round_time)." um ". date("H:i",$new_round_time)." gesperrt."); }

?>
