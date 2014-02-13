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

$serviceId = 'FILL WITH GIVEN SERVICE ID';
$serviceSecretKey = 'FILL WITH GIVEN SECRET KEY';

# User have been authenticated by Keydentify ?
if (!isset($_POST) || !isset($_POST['keydResponse'])) {

	# 1 - User need to be identified : launch keydentify process
	# ---------------------------------------------------------------------------------------------
	# DO WHAT YOU NEED TO DO HERE : Make tests on user, his card, ....
	
	
	# 2 - Contact Keydentify to :
	#    + confirm user authentication
	#    + enroll user and his authenticcation device
	# ---------------------------------------------------------------------------------------------
	
	# USER ID => What you want but must be constant for this user and unique for your service [card id, hash(card id), ...]
	$userId = '3312'; //'mycardnumber';
	
	
	# 3 - Fill optionnal data
	# ---------------------------------------------------------------------------------------------
	
	# USER NAME / LOGIN -- Optional, What you want. Will be store in Keydentify Backoffice
	$userName = '';
		
	# Service locale, actually 'en' / 'fr' => Optional, by default 'en'
	$locale = 'fr';
	
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
	$requestAuth = KeydentifyAPI::requestAuth($serviceId, $userId, $serviceSecretKey);
	
	# Full request with more params
	//$requestAuth = KeydentifyAPI::requestAuth($serviceId, $userId, $serviceSecretKey, $userName, $locale, $redirectTo, $userIP, $authType, $userEmail, $userPhoneNumber);
	
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<title>Keydentify :: SDK PHP Web</title>
	   		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	   	</head>
	   	<body>
	<?php 	
	
	if (is_null($requestAuth) || !$requestAuth) { # An error occured : unable to contact Keydentify
		echo $requestAuth;
	} else if (!is_array($requestAuth)) {         # An warning is sent by Keydentify Plugin or Keydentify server
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
		
	?>
	</body>
	</html>
	<?php 

} else {
	
	# 5 - Receive user response via a post form (contains Keydentify response)
	# ---------------------------------------------------------------------------------------------

	$status = 'failure : ';
	$error = '';
	
	# Unlike user login, the user id should not be shown on client app.
	# We need to retreive the user id it with userName / userLogin
	$tmpUserId = '3312';
	
	
	# 6 - Analyse user response, check it's integrity, compare result
	# ---------------------------------------------------------------------------------------------
	
	$check = KeydentifyAPI::checkKeydentifyResponse($serviceId, $tmpUserId, $serviceSecretKey, $_POST);
	if (!is_bool($check)) {
		$error = __("Keydentify - Two-Factor Authentication failed", "keydentify")." : ".$check;
	} else {
		$status = 'success';
		
		# 7 - You can then log in user, validate your request, ....
		# ---------------------------------------------------------------------------------------------
	}
	
	# 8 - Redirect user and inform him, ....
	# ---------------------------------------------------------------------------------------------
	echo $status;
	echo $error;	
	//redirect($_POST['redirect_to']);
}
?>
