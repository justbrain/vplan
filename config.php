<?php
$config = array(
	// IMAP settings
	'imap' => array(
		'server' => '',				// Server
		'port' => 143,				// Port
		'mailbox' => 'INBOX',			// Mailbox
		'username' => '',			// Username
		'password' => '',			// Password
		'from' => '',				// From must contain
		'subject_today' => 'heute',		// Subject must contain
		'subject_tomorrow' => 'morgen',		// Subject must contain
	),

	// Database settings
	'database' => array(
		'dsn' => 'mysql:host=;dbname=',
		'username' => '',
		'password' => '',
	),

	// Lessons
	'lessons' => array(
		1 => array(
			'begin' => array(8, 20, 0),
			'end' => array(9, 5, 0),
		),
		2 => array(
			'begin' => array(9, 10, 0),
			'end' => array(9, 55, 0),
		),
		3 => array(
			'begin' => array(10, 0, 0),
			'end' => array(10, 45, 0),
		),
		4 => array(
			'begin' => array(11, 10, 0),
			'end' => array(11, 55, 0),
		),
		5 => array(
			'begin' => array(12, 0, 0),
			'end' => array(12, 45, 0),
		),
		6 => array(
			'begin' => array(12, 45, 0),
			'end' => array(13, 30, 0),
		),
		7 => array(
			'begin' => array(13, 30, 0),
			'end' => array(14, 15, 0),
		),
		8 => array(
			'begin' => array(14, 15, 0),
			'end' => array(15, 0, 0),
		),
		9 => array(
			'begin' => array(15, 0, 0),
			'end' => array(15, 45, 0),
		),
		10 => array(
			'begin' => array(15, 45, 0),
			'end' => array(16, 30, 0),
		),
		11 => array(
			'begin' => array(16, 30, 0),
			'end' => array(17, 15, 0),
		),
	),
);
?>
