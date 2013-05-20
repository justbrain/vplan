<?php
$today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
$tomorrow = $today + 60 * 60 * 24;

$days = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');

$date_today = $current_dates[0];
$day_today = $days[date('w', $date_today)];
if ($date_today == $today) $day_today = 'Heute';
elseif ($date_today == $tomorrow) $day_today = 'Morgen';

if (isset($current_dates[1])) {
	$date_tomorrow = $current_dates[1];
	$day_tomorrow = $days[date('w', $date_tomorrow)];
	if ($date_tomorrow == $today) $day_tomorrow = 'Heute';
	elseif ($date_tomorrow == $tomorrow) $day_tomorrow = 'Morgen';
}
?>
<!doctype html>
<html>
	<head>
		<!-- Copyright (C) <?= date('Y') ?> Maximilian von Buelow -->

		<title>Vertretungsplan | IGS Mainz-Bretzenheim</title>

		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta charset="UTF-8" />
		<meta name="robots" content="INDEX,NOFOLLOW" />
		<meta name="description" content="Der Vertretungsplan der IGS Mainz-Bretzenheim" />
		<meta name="keywords" content="vertretungsplan, igs, mainz, bretzenheim" />
		<meta name="author" content="Maximilian von Buelow" />
		<meta name="publisher" content="Felix Buchmueller, Lena Goeth, Maximilian von Buelow, Noah Haas" />

		<link rel="icon" href="/images/calendar_icon.png" />
		<link rel="shortcut icon" type="image/vnd.microsoft.icon" href="/favicon.ico" />
		<link rel="stylesheet" type="text/css" href="/stylesheets/style_<?php echo $env; ?>.css" />

<?php if ($env === 'mobile') { ?>
		<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" />
		<meta name="viewport" content="width=device-width" />
<?php } ?>

		<script type="text/javascript" src="/javascripts/jquery-1.7.2.min.js"></script>
		<script type="text/javascript">var env = <?php echo json_encode($env); ?>;</script>
		<script type="text/javascript" src="/javascripts/script.js?v=4"></script>
	</head>
	<body>
		<div id="header-wrapper">
			<div id="header">
				<div id="logo"><img src="/images/calendar.png" alt="" style="width: 30px; height: 30px; margin-bottom: -5px;" /> VPlan</div>
<?php if ($request[1] != 'imprint') { ?>
				<div id="plan-menu">
					<ul>
						<li><a href="#today" id="button-plan-today" class="button-plan"><?= $day_today ?></a></li>
<?php if (isset($day_tomorrow)) { ?>
						<li><a href="#tomorrow" id="button-plan-tomorrow" class="button-plan"><?= $day_tomorrow ?></a></li>
<?php } ?>
					</ul>
					<div class="clearfix"></div>
				</div>
<?php } ?>
			</div>
		</div>
		<div id="content">
<?= $content ?>
		</div>
		<div id="footer-wrapper">
			<div id="footer">
				<a href="/">Vertretungsplan</a> | <a href="/imprint">Impressum</a>
			</div>
		</div>
	</body>
</html>
