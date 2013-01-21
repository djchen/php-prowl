<?php
ini_set('display_errors', 'on');

require_once('class.prowl.php');

define('LINE_ENDING', isset($_SERVER['HTTP_USER_AGENT']) ? '<br />' : "\n");

try {
	$config = array(
		'apiKey' => '', // provide an API key to test
		'debug' => true
	);
	$prowl = new Prowl($config);

	$notification = array(
		'application' => 'PHP Prowl',
		'event' => 'Test Event',
		'description' => 'This is a test of the PHP Prowl library',
		'url' => 'http://github.com/djchen', // optional
		'priority'  => 0 // optional
	);

	$message = $prowl->add($notification);	
	
} catch (Exception $message) {
	echo 'Exception: ' . $message->getMessage() . LINE_ENDING;
}
