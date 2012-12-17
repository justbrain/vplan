<?php
/**
 * IGS Mainz-Bretzenheim representation plan
 *
 * @author     Maximilian von Buelow <max@m9x.de>
 * @copyright  2012 Maximilian von Buelow
 * @link       https://github.com/justbrain/vplan
 */

if ($_SERVER['HTTP_HOST'] !== 'vplan.igsmz.net' && $_SERVER['HTTP_HOST'] !== 'localhost') {
	header('Location: http://vplan.igsmz.net');
	exit;
}

session_start();
session_cache_expire(60 * 24 * 365 * 10);

require_once './config.php';

$request = explode('/', $_SERVER['REDIRECT_URL']);
array_shift($request);

$file = $request[count($request) - 1];
$file_splitted = explode('.', $file);
$file_name = implode('.', array_slice($file_splitted, 0, -1));
$file_extension = end($file_splitted);

require_once('./lib/vplan/index.php');

$dbh = new PDO($config['database']['dsn'], $config['database']['username'], $config['database']['password']);
$vplan = new VPlan($dbh, $config['lessons']);
$current_dates = $vplan->getCurrentDates();
if (($file_extension === 'json' || $file_extension === 'isc') && count($request) <= 2) {
	$mbox = imap_open('{' . $config['imap']['server'] . ':' . $config['imap']['port'] . '}' . $config['imap']['mailbox'], $config['imap']['username'], $config['imap']['password']);
	$vplan->setMbox($mbox);

	$vplan->updatePlan($config['imap']['from'], $config['imap']['subject_today'], $config['imap']['subject_tomorrow']);

	if ($file_extension === 'json') {
		$date_name = $file_name;
		if ($file_name === 'tomorrow') $date = end($current_dates);
		else $date = current($current_dates);

		header('Content-Type: application/json');
		echo $vplan->getPlanJSON($date, (count($request) === 2) ? $request[0] : null);
	}
	else if ($file_extension === 'isc') {
		$class_name = $file_name;
		$subjects = (isset($_GET['subjects']) && is_array($_GET['subjects'])) ? $_GET['subjects'] : array();

		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: inline; filename=calendar.ics');
		echo $vplan->getPlanISC($class, $subjects);
	}

	exit;
}


include './lib/mobile/Mobile_Detect.php';
$detect = new Mobile_Detect();

if ($detect->isMobile()) $env = 'mobile';
else $env = 'desktop';

ob_start();
switch ($request[0]) {
	case 'imprint':
		include './templates/imprint.tpl.php';
		break;
	default:
		include './templates/plan.tpl.php';
		break;
}
$content = ob_get_contents();
ob_end_clean();

include './templates/page.tpl.php';