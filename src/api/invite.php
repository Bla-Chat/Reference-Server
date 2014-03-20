<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Bla Chat - invite</title>
<link rel="stylesheet" type="text/css" href="../style.css">
<script type="text/javascript">
function validateForm()
{
  var x=document.forms["login"]["user"].value;
  if (x==null || x=="" || !x.match(/^[a-z0-9]+$/i)) {
    alert("Username is required and must be alphanumerical!");
    return false;
  }

  x=document.forms["login"]["pw"].value;
  if (x==null || x=="" || x.length < 6 || !x.match(/^[a-z0-9]+$/i)) {
    alert("Password must be more than 6 digits and alphanumerical.");
    return false;
  }

  x=document.forms["login"]["name"].value;
  //if (x==null || x=="" || !x.match(/^[a-z]+$/i)) {
   // alert("Real name must be filled out. I asume that names are only consisting out of a-z.");
   // return false;
  //}
  
  x=document.forms["login"]["email"].value;
  var atpos=x.indexOf("@");
  var dotpos=x.lastIndexOf(".");
  if (atpos<1 || dotpos<atpos+2 || dotpos+2>=x.length) {
    alert("Not a valid e-mail address");
    return false;
  }

  x=document.forms["login"]["about"].value;
  if (x==null || x=="") {
    alert("About must be filled out");
    return false;
  }
}
</script>
</head>
<body>
<div id="topBar">Bla - invite friends <a id="invite" href='../index.php'>chat</a></div>
<div align="center">
<BR>
<BR>
<?PHP
	include "settings.php";

	function writeCreateUserForm () {
		echo ("<div id='formular'>");
		echo ("<form name='login' action='invite.php' onsubmit='return validateForm()' method='post'>");
		echo ("Create a 'bla' account<BR><BR>");
		echo ("<table style='width:600px'>");
		echo ("<tr><td><b>Username</b> </td><td><input type='text' name='user' size='32' /></td><td><i>(only used for login)</i></td></tr>");
		echo ("<tr><td><b>Password</b></td><td><input type='password' name='pw' size='32' /></td><td><i>(alphanumerical)</i></td></tr>");
		echo ("<tr><td><b>E-Mail</b></td><td><input type='text' name='email' size='32' /></td><td><i>(only verification & password-reset)</i></tr>");
		echo ("<tr><td> Real Name</td><td><input type='text' name='name' size='32' /> </td><td><i>(shown to friends)</i></td></tr>");
		echo ("<tr><td> About you: </td><td><input type='text' name='about' size='32' /></td><td></td></tr>");
		echo ("</table>");
		echo ("Fields marked with <b>bold font</b> are not changable without contacting the support.<BR>");
		echo ("<div class='agreement' align='left'><b>Agreement</b><BR><p> I do not use this chat for any illegal purpose. I am aware that the host and creator of this chat is not able to check all content. When illegal content is detected I will immediatly report this, so the host can remove this content.</p><p> When there is illegal content detected and german law forces the host to give that data away, the data will be handed to german justice. However, whereas data is stored in germany my data will be safe as long as I do no illegal activities. </p><p>In the future the data will be stored encrypted. So I am aware that forgetting my password can cause a loss of data, whereas they will only be decryptable by my current password.</p><b>Disclaimer</b><p>According to german law the host is not responsible for inapropriate content. If it is removed as soon as the host notices it or it is reported.</p><p>It is technically not possible that the host checks conversations, where he is not part of. Inapropriate content can only be detected by reporting it.</p></div>");
		echo ("<input type='hidden' name='code' value='".$_GET['code']."' />");
		echo ("<input type='submit' value='Accept & Create Account' />");
		echo ("</form>");
		echo ("</div>");
	}
	
	function randomstring($length = 32) {
  		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
  		srand((double)microtime()*1000000);
  		$i = 0;
  		while ($i < $length) {
    		$num = rand() % strlen($chars);
    		$tmp = substr($chars, $num, 1);
    		$pass = $pass . $tmp;
    		$i++;
  		}
  		return $pass;
	}
    
    @mysql_connect($server, $username, $password) or die ("Cannot connect!");
    @mysql_select_db($database) or die ("Cannot select db.");
    mysql_set_charset("UTF8");
    
    function authentificate($user, $pw) {
    	$query = 'SELECT `Salt` FROM `users` WHERE `Nick`="'.htmlspecialchars($user).'";';
		$result = mysql_query($query);
    	$num = mysql_num_rows($result);
    	if ($num != 1) {
        	return false;
    	}
		$salt = mysql_result($result,0,"Salt");
		$pw = md5($pw.$salt);
		
		$query = 'SELECT `Nick` FROM `users` WHERE `Nick`="'.htmlspecialchars($user).'" AND `Password`="'.$pw.'";';
    	$result = mysql_query($query);
    	$num = mysql_num_rows($result);
    	
    	if ($num != 1) {
        	return false;
    	}
    	return true;
    }
    
    function validateCode($code) {
    	$query = "SELECT `Inviter` FROM `invitations` WHERE `Code`='$code'";
    	$result = mysql_query($query);
    	if (mysql_num_rows($result) != 1) {
    		return false;
    	}
    	return true;
    }
	
	function formCreator() {
	if (isset($_POST['user']) && isset($_POST['pw'])) {
		if (isset($_POST['code'])) {
			if (validateCode(htmlspecialchars($_POST['code']))) {
				$nick = htmlspecialchars($_POST['user']);
				$pw = htmlspecialchars($_POST['pw']);
				$name = htmlspecialchars($_POST['name']);
				$email = htmlspecialchars($_POST['email']);
				$description = htmlspecialchars($_POST['about']);
				$code = htmlspecialchars($_POST['code']);
				
				// Check if values are valid
				if (!preg_match("/[a-zA-Z0-9]+/", $nick)) {
					echo("Nick can use [a-zA-Z0-9]<BR>");
					echo("(Hint: Use the back button of your browser)");
					return;
				}
				if (!preg_match("/[a-zA-Z0-9]+/", $pw)) {
					echo("Pasword must use [a-zA-Z0-9]<BR>");
					echo("(Hint: Use the back button of your browser)");
					return;
				}
				if (!preg_match("/[A-Z][a-z]+( [A-Z][a-z]*)*/", $name)) {
					echo("Please provide your real name.<BR>");
					echo("(Hint: Use the back button of your browser)");
					return;
				}
				if (!preg_match("/.*@.*\\..*/", $email)) {
					echo("Please provide your real mail.<BR>");
					echo("(Hint: Use the back button of your browser)");
					return;
				}
				
				$salt = randomstring(6);
				$pw = md5($pw.$salt);
				
				// Add new user...
				$query = "INSERT INTO `users`(`Nick`, `Password`, `Salt`, `RealName`, `Status`, `Email`, `Image`, `Description`) VALUES ('$nick','$pw','$salt','$name',0,'$mail','null','$description');";
				$result = mysql_query($query);
				
				// Add inviter as friend
				$query = "SELECT `Inviter` FROM `invitations` WHERE `Code`='$code';";
				$result = mysql_query($query);
				$line = mysql_fetch_assoc($result);
				$inviter = $line['Inviter'];
				if ($inviter == null) {
					echo("Internal error. You need a new invitation code.");
				} else {
					$query = "INSERT INTO `contacts`(`Nick`, `Friend`) VALUES ('$nick','$inviter');";
					mysql_query($query);
					$query = "INSERT INTO `contacts`(`Nick`, `Friend`) VALUES ('$inviter','$nick');";
					mysql_query($query);
					
					echo ("<div align='center'><BR>Continue here:<BR><BR><BR><div class='dbutton'><a class='button' href='".$serverAddress."'>PC/Tablet</a></div><BR><BR><BR><div class='dbutton'><a class='button' href='".$serverAddress."/mobile.html'>Non-Android Mobile</a></div><BR><BR><BR><div class='dbutton'><a class='button' href='https://raw.github.com/penguinmenac3/BlaChat/master/app/bla.apk'>Android (APK)</a></div><BR><BR> Have fun!</div>");
				}
				
				// Delete invitation code
				$query = "DELETE FROM `invitations` WHERE `code`='$code';";
				mysql_query($query);
			} else {
				// Invalid code
				echo ("Sorry, your code has expired or never existed.");
			}
		} else {
			$nick = htmlspecialchars($_POST['user']);
			$pw = htmlspecialchars($_POST['pw']);
			if (authentificate($nick, $pw) == true) {
				// Generate invitation link.
				$code = htmlspecialchars(randomstring(32));
				$query = "INSERT INTO `invitations`(`Inviter`, `Code`) VALUES ('$nick','$code');";
				mysql_query($query);
				include "settings.php";
				echo("Pass this link to your friend: <BR><BR><BR><div class='dbutton'><a class='button' href='".$serverAddress."/api/invite.php?code=".$code."'>".$serverAddress."/api/invite.php?code=".$code."</a></div>");
			} else {
				echo ("Sorry, you have no permission.");
			}
		}
	} else if (isset($_GET['code'])) {
		if (validateCode(htmlspecialchars($_GET['code']))) {
			writeCreateUserForm();
		} else {
			echo ("Sorry, your code has expired or never existed.");
		}
	} else {
		echo ("<div id='formular'>");
		echo ("<form name='login' action='invite.php' method='post'>");
		echo ("Generate an invitation link (authentification required)<BR><BR>");
		echo ("Username: <input type='text' name='user' size='32' /><BR>");
		echo ("Password: <input type='password' name='pw' size='32' /><BR>");
		echo ("<input type='submit' value='Generate' />");
		echo ("</form>");
		echo ("</div>");
	}
	}
	
	formCreator();
	mysql_close();
?>
</div>
</body>
</html>
