<?php
/**
 * Keydentify SDK PHP Web
 *
 * Keydentify(tm) : Two Factor Authentication (http://www.keydentify.com)
 * Copyright (c) SAS Keydentify.  (http://www.keydentify.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) SAS Keydentify.  (http://www.keydentify.com)
 * @link          http://www.keydentify.com Keydentify(tm) Two Factor Authentication
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

include('lib/keydentifyAPI.php');

define('USER_LOGIN_PREFIX', 'Sample ');

function demo() {
	
	$serviceId = 'FILL WITH GIVEN SERVICE ID';
	$secretKey = 'FILL WITH GIVEN SECRET KEY';
	
	$status = 0;
	
	if (isPost() && isset($_POST['custId'])) {
		$step = 'KEYDENTIFY_CALL_AUTH_SERVICE';
	} else if (isPost() && isset($_POST['keydResponse'])) {
		$step = 'KEYDENTIFY_CHECK_RESPONSE';
	} else {
		$step = 'YOUR_APP_SIGN_IN_PROCESS';
	}

	
	
	#####################################################################
	## THIS PART IS YOUR RESPONSABILITY WHERE YOU PROVIDE TO YOUR
	## CUSTOMERS A WAY TO AUTHENTICATE
	#####################################################################
	if ($step == 'YOUR_APP_SIGN_IN_PROCESS') {
		
		# 1 - User need to be identified : launch keydentify process
		# ---------------------------------------------------------------------------------------------
		# DO WHAT YOU NEED TO DO HERE : Make tests on user, on his password, ....
		
		$custId = (isset($_GET['custId'])) ? $_GET['custId'] : '';
		
		?>
		<form accept-charset="utf-8" onsubmit="return formOnSubmit();" method="post" id="KeydentifyCheckAuthForm" class="form-inline">
			<p>If you already have enrol a customer ID with one of your device, retry it to see how Keydentify is easy to use.
			<br><br>Else, enter a customer ID, and if nobody have already reserved it, lock it for everyone.</p>
			<div class="spacer">
				<div class="form-group">
					<input id="UserId" class="form-control" type="text" required="required" value="<?php echo $custId; ?>" placeholder="Enter a customer id" name="custId">
				</div>
				<button class="btn btn-primary" type="submit">Sign in</button>
			</div>
		</form>
		<p style="margin-top: 15px;">>Authentication secured by <a href="http://www.keydentify.com" target="_blank">Keydentify</a>. Your smartphone is required!</p>
		
		<script type="text/javascript">
			function formOnSubmit() {
				if ($('#UserId').val() == '') {
					alert("You must enter a customer ID");
					return false;
				} else {
					return true;
				}
			}
			document.getElementById('UserId').focus();
		</script>
		<?php 
	}
	
	
	#####################################################################
	## YOU NEED TO CHECK IF THIS CUSTOMER IS REALLY THE ONE HE SAID HE IS
	## CALL KEYDENTIFY SERVICE TO CHECK HIS IDENTITY
	#####################################################################
	if ($step == 'KEYDENTIFY_CALL_AUTH_SERVICE') {
		
		# 2 - Contact Keydentify to :
		#    + confirm user authentication
		#    + enroll user and his authenticcation device
		# ---------------------------------------------------------------------------------------------
		
		# USER ID => What you want but must be constant for this user and unique for your service [card id, hash(card id), ...]
		$custId = $_POST['custId']; // md5($customerID);
		
		
		# 3 - Fill optionnal data
		# ---------------------------------------------------------------------------------------------
		
		# USER NAME / LOGIN -- Optional, What you want. Will be store in Keydentify Backoffice
		$userName = USER_LOGIN_PREFIX.$custId;
			
		# Service locale, actually 'en' / 'fr' / 'de' => Optional, by default 'en'
		$locale = 'en';
		
		# URL where the user will be redirected after it's authentication process
		$redirectTo = 'http://www.google.com';
		
		# User IP address -- Optional
		$userIP = $_SERVER["REMOTE_ADDR"];
		
		# Auth Type : 1 = with Keydentify app, 2 = with 3D Secure by SMS, 3 = with 3D Secure by Phone Call -- Optional, by default 1
		$authType = 1;
		
		# Optional except when using authType 2 (SMS) or 3 (phone call)
		$userEmail = $userPhoneNumber = '';
		
		# 4 - Connect to Keydentify Server
		# ---------------------------------------------------------------------------------------------	
		$requestAuth = KeydentifyAPI::requestAuth($serviceId, $custId, $secretKey, $userName, $locale, '', $userIP);
		
		# Full request with more params
		//$requestAuth = KeydentifyAPI::requestAuth($serviceId, $custId, $secretKey, $userName, $locale, $redirectTo, $userIP, $authType, $userEmail, $userPhoneNumber);
		
		
		if (is_null($requestAuth) || !$requestAuth) { # An error occured : unable to contact Keydentify
			echo $requestAuth;
		} else if (!is_array($requestAuth)) {         # A warning is sent by Keydentify Plugin or Keydentify server
			echo $requestAuth;
		} else {                                      # Ask the user to confirm his identity
			?>		   	
			<!-- Website secure by Keydentify - Two-Factor Authentication http://www.keydentify.com -->
			<div id="keydentify">
				<link href="css/keydentify.css" rel="stylesheet" type="text/css">
				<script src="js/sockjs-0.3.4.min.js" type="text/javascript"></script>
				<script src="js/vertxbus.js" type="text/javascript"></script>
				<script src="js/keydentify.js" type="text/javascript"></script>
				
				<form accept-charset="utf-8" method="post" id="KeydentifyCheckAuthForm">
					<?php echo $requestAuth['html'];?>
				</form>
			</div>
			<!-- /Keydentify - Two-Factor Authentication -->	
			<?php 
		}	
	}
	
	
	#####################################################################
	## HERE IS YOUR CUSTOMER BROWSER REPLY
	## AFTER KEYDENTIFY PROCESS
	#####################################################################
	if ($step == 'KEYDENTIFY_CHECK_RESPONSE') {
		
		# 5 - Receive user response via a post form (contains Keydentify response)
		# ---------------------------------------------------------------------------------------------
	
		$status = 'failure : ';
		$error = '';

		# If login have been given, use it to retreive your customer id in your db
		if ($_POST['login'] != '') {
			// $custId = ....
			
			# For this demo, with have build a user login and pass it to Keydentify
			# In real case, it is not mandatory to send us your user login
			$custId = str_replace(USER_LOGIN_PREFIX, '', $_POST['login']);
			
		} else if (isset($_POST['suid'])) {
			# If you have not send your customer login to keydentify API, his ID have been set in html form
			$custId = $_POST['suid'];
		}

		
		# 6 - Analyse user response, check it's integrity, compare result
		# ---------------------------------------------------------------------------------------------
		
		$check = KeydentifyAPI::checkKeydentifyResponse($serviceId, $custId, $secretKey, $_POST);
		if (!is_bool($check)) {
			$error = __("Keydentify - Two-Factor Authentication failed", "keydentify")." : ".$check;
		} else {
			if (isset($_POST['keydDelay']) && $_POST['keydDelay'] > 60) {
				$status = 'Congratulation, you have successfully enroll your customer ID '.$custId.'. <a href="sample.php?custId='.$custId.'">Click here to retry and discover the simplicity of Keydentify</a>';
			} else {
				$status = 'Congratulation, you have successfully confirmed and in a secure way your customer with ID '.$custId.'. <a href="sample.php">Click here to retry</a>';
			}
			
			# 7 - With this auth success, you can now really log in your user ....
			# ---------------------------------------------------------------------------------------------
			// Do what you have to do in your app ...
			
			
		}
		
		# 8 - Redirect user and inform him, ....
		# ---------------------------------------------------------------------------------------------
		echo $status;
		echo $error;	
		//redirect($_POST['redirect_to']);
	}
}

function isPost() {
	return isset($_POST) && count($_POST)>0;
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Démonstration :: Keydentify</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
		<style type="text/css">
		.center {
			width: 450px;
			margin: 10px auto;
		}
		.spacer {
			margin-top: 30px;
		}
		</style>
	</head>
	<body>
		<div class="center">
			<?php demo(); ?>
		</div>
	</body>
	<script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="http://netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
</html>
