<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Hangout</title>
<link rel="stylesheet" type="text/css" href="invite.css">
</head>
<body>
<?PHP
	include "settings.php";

	function writeCreateUserForm () {
		echo ("<div id='formular'>");
		echo ("<form name='login' action='invite.php' method='post'>");
		echo ("Create a 'bla chat' account:<BR>");
		echo ("Username: <input type='text' name='user' size='32' />(must be alphanumerical, only used for login)<BR>");
		echo ("Password: <input type='password' name='pw' size='32' />(use more than 6 digits and an alphanumerical string)<BR>");
		echo ("Real Name: <input type='text' name='name' size='32' /> (shown to friends, using your real name is highly recommended)<BR>");
		echo ("E-Mail: <input type='text' name='email' size='32' /> (upcoming feature: verification & reset password, not published but required)<BR>");
		echo ("About you: <input type='text' name='about' size='32' />(upcoming feature, just insert anything here, not used yet and will be editable)<BR>");
		echo ("<input type='hidden' name='code' value='".$_GET['code']."' />");
		echo ("<input type='submit' value='Generate' />");
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
					
					echo ("<div align='center'>Continue at:<BR><a href='".$serverAddress."'>".$serverAddress." (pc)</a><BR>or<BR><a href='".$serverAddress."/mobile.html'>".$serverAddress."/mobile.html (mobile)</a><BR><BR>You can invite others by using this site:<BR><a href='".$serverAddress."/api/invite.php'>".$serverAddress."api/invite.php</a><BR><BR> Have fun!</div>");
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
				echo("Copy that link and hand it to a friend: ".$serverAddress."/api/invite.php?code=".$code);
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
		echo ("Generate invitation link:<BR>");
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
</body>
</html>
