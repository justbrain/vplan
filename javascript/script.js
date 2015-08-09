/**
 * IGS Mainz-Bretzenheim representation plan
 *
 * @author     Maximilian von Buelow <max@m9x.de>
 * @copyright  2012 Maximilian von Buelow
 * @link       https://github.com/justbrain/vplan
 */

$(function() {
	$('.javascript-available').show();
	$('.javascript-unavailable').hide();

	if ($('#plan').length == 0) return;

	var hash = decodeHash(window.location.hash.substr(1));
	initPlan(hash.date, hash.cls);

	var effects = (env === 'desktop');
	$('.button-plan').click(function() {
		var	hrefSplit = $(this).attr('href').split('#'),
			hash = decodeHash(hrefSplit[hrefSplit.length - 1]);
		changePlan(hash.date, hash.cls, effects);
	});
	$('#classes a').click(function() {
		var	hrefSplit = $(this).attr('href').split('#'),
			hash = decodeHash(hrefSplit[hrefSplit.length - 1]);
		changePlan(hash.date, hash.cls, effects);
	});
});

function decodeHash(hash) {
	var hashData = hash.split(',');

	return {
		date: hashData[0] || 'today',
		cls: hashData[1] || null,
	};
}
function hashDataToUrl(data) {
	return '#' + data.date + ',' + (data.cls || '');
}
function updateHashLinks(date, cls) {
	$('#classes a').each(function() {
		var	hrefSplit = $(this).attr('href').split('#'),
			hash = decodeHash(hrefSplit[hrefSplit.length - 1]);

		hash.date = date;

		$(this).attr('href', hashDataToUrl(hash));
	});

	$('.button-plan').each(function() {
		var	hrefSplit = $(this).attr('href').split('#'),
			hash = decodeHash(hrefSplit[hrefSplit.length - 1]);

		hash.cls = cls;

		$(this).attr('href', hashDataToUrl(hash));
	});
}

function initPlan(date, cls, cb) {
	$('#button-plan-' + date).addClass('active');

	loadPlan(date, cls, function() {
		showPlan(false, cb);
	});
}
function changePlan(date, cls, effects, cb) {
	$('.button-plan.active').removeClass('active');
	$('#button-plan-' + date).addClass('active');

	hidePlan(effects, function() {
		loadPlan(date, cls, function() {
			showPlan(effects, cb);
		});
	});
}

function showPlan(effects, cb) {
	if (effects) {
		$('#plan-content').animate({
			left: '0px',
		}, 100, function() {
			$('body').css('overflow-x', 'visible');

			cb && cb();
		});
	}
	else {
		$('#plan-content').show();
		cb && cb();
	}
}
function hidePlan(effects, cb) {
	if (effects) {
		var move = $('#plan-content').offset().left + $('#plan-content').width();
		$('#plan-content').animate({
			left: (move * -1) + 'px',
		}, 100, function() {
			$('#plan-content').css('left', move + 'px');

			$('body').css('overflow-x', 'hidden');

			cb && cb();
		});
	}
	else {
		$('#plan-content').hide();
		cb && cb();
	}
}

function findLessonByDate(lessons, date) {
	var	H = date.getHours(),
		m = date.getMinutes(),
		s = date.getSeconds();

	for (var i in lessons) {
		if (lessons[i].begin[0] == H && lessons[i].begin[1] == m && lessons[i].begin[2] == s) return i;
	}
}

function loadPlan(date, cls, cb) {
	$('#plan-date').html('Lade...');

	updateHashLinks(date, cls);

	$.get((cls ? '/' + cls : '') + '/' + date + '.json', function(data) {
		var	plan = data.plan,
			types = data.types,
			lessons = data.lessons;

		plan = {
			info: {
				date: plan[0] && plan[0].date,
				updated: plan[0] && plan[0].updated,
			},
			plan: plan,
		};

		var todayDate = new Date();
		todayDate.setHours(0);
		todayDate.setMinutes(0);
		todayDate.setSeconds(0);

		var	today = todayDate.getTime(),
			tomorrow = today + 1000 * 60 * 60 * 24;

		var planDate = new Date();
		planDate.setTime(plan.info.date * 1000);

		var planUpdated = new Date();
		planUpdated.setTime(plan.info.updated * 1000);

		var	days = [ 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', ],
			day = days[planDate.getDay()];

		if (plan.info.date == Math.floor(today / 1000)) day = 'Heute';
		else if (plan.info.date == Math.floor(tomorrow / 1000)) day = 'Morgen';

		// Update menu
		if (date == 'today') $('#plan1').html(day);
		else $('#plan2').html(day);

		var html = '';
		if (env === 'desktop') {
			html +=	'<table>' +
				'<thead>' +
				'<tr>' +
				'<th>Stunde(n)</th>' +
				'<th>Klasse(n)</th>' +
				'<th>Kurs</th>' +
				'<th>Raum</th>' +
				'<th>Art</th>' +
				'<th>Beschreibung</th>' +
				'</tr>' +
				'</thead>' +
				'<tbody>';
		}

		var type = 'odd';
		for (var i in plan.plan) {
			if (env === 'desktop') {
				html +=	'<tr class="' + type + ' plan-type-' + plan.plan[i].type + '">' +
					'<td class="plan-lessons">' + findLessonByDate(lessons, new Date(plan.plan[i].time * 1000)) + '</td>' +
					'<td class="plan-classes">' + plan.plan[i]['class'] + '</td>' +
					'<td class="plan-subject">' + plan.plan[i].subject + '</td>' +
					'<td class="plan-room">' + (plan.plan[i].room ? plan.plan[i].room : '-') + '</td>' +
					'<td class="plan-type">' + (types[plan.plan[i].type].nice ? types[plan.plan[i].type].nice : types[plan.plan[i].type].name) + '</td>' +
					'<td class="plan-description">' + (plan.plan[i].description ? plan.plan[i].description : '') + '</td>' +
					'</tr>';
			}
			else {
				html +=	'<div class="plan-row ' + type + ' plan-type-' + plan.plan[i].type + '">' +
					'<span class="plan-subject">' + plan.plan[i].subject + '</span>' +
					'<span class="plan-classes">' + plan.plan[i]['class'] + '</span>' +
					'<span class="plan-lessons">Std. ' + findLessonByDate(lessons, new Date(plan.plan[i].time * 1000)) + '</span>' +
					(plan.plan[i].room ? '<span class="plan-room">Raum ' + plan.plan[i].room + '</span>' : '') +
					'<span class="plan-description">' + (plan.plan[i].description ? plan.plan[i].description : '') + '</span>' +
					'<span class="plan-type">' + (types[plan.plan[i].type].nice ? types[plan.plan[i].type].nice : types[plan.plan[i].type].name) + '</span>' +
					'</div>';
			}

			type = (type == 'odd' ? 'even' : 'odd');
		}
		if (plan.plan.length === 0) {
			if (env === 'desktop') {
				html +=	'<tr class="even">' +
					'<td colspan="6">Keine Einträge vorhanden.</td>' +
					'</tr>';
			}
		}

		if (env === 'desktop') {
			html +=		'</tbody>';
					'</table>';
		}

		if (plan.plan.length !== 0) $('#plan-date').html(day + ' (' + planDate.getDate() + '.' + (planDate.getMonth() + 1) + '.' + planDate.getFullYear() + ')');
		else $('#plan-date').html('');
		$('#plan-content').html(html);
		if (plan.plan.length !== 0) $('#plan-updated').html('Stand: ' + planUpdated.getDate() + '.' + (planUpdated.getMonth() + 1) + '.' + planUpdated.getFullYear() + ' ' + planUpdated.getHours() + ':' + planUpdated.getMinutes() + ':' + planUpdated.getSeconds());

		cb && cb();
	}, 'json');
}