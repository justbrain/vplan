<?php
class VPlan {
	private $dbh;
	private $mbox;

	public static $expire = 300; // 5 minutes

	public function __construct($dbh, $lessonTimes) {
		$this->dbh = $dbh;
		$this->dbh->exec('SET NAMES utf8');
		$this->lessonTimes = $lessonTimes;

		$this->types = array();
		$this->tids = array();
		$types_q = $this->dbh->query('SELECT tid, name, nice FROM type');
		while ($type = $types_q->fetch(PDO::FETCH_OBJ)) {
			$this->types[$type->tid] = $type;
			$this->tids[$type->name] = $type->tid;
		}
	}

	public function setMbox($mbox) {
		$this->mbox = $mbox;
	}

	public function getCurrentDates() {
		$current_dates = array();

		$current_dates_q = $this->dbh->prepare('SELECT date FROM plan WHERE date > ? GROUP BY DAY(FROM_UNIXTIME(date)), MONTH(FROM_UNIXTIME(date)), YEAR(FROM_UNIXTIME(date)) ORDER BY date ASC LIMIT 2');
		$current_dates_q->execute(array(mktime(0, 0, 0, date('n'), date('j'), date('Y'))));
		while ($current_date = $current_dates_q->fetch(PDO::FETCH_OBJ)) {
			$current_dates[] = mktime(0, 0, 0, date('n', $current_date->date), date('j', $current_date->date), date('Y', $current_date->date));
		}

		return $current_dates;
	}
	public function mailCheck($from, $subject_today, $subject_tomorrow) {
		$headers_today = imap_search($this->mbox, 'FROM ' . $from . ' SUBJECT ' . $subject_today);
		$headers_tomorrow = imap_search($this->mbox, 'FROM ' . $from . ' SUBJECT ' . $subject_tomorrow);

		if (!$headers_today && !$headers_tomorrow) return false;

		$id_today = end($headers_today);
		$id_tomorrow = end($headers_tomorrow);

		$header_tomorrow = imap_headerinfo($this->mbox, $id_tomorrow);
		$last_message_time = strtotime($header_tomorrow->date);

		$res = false;
		if (!$this->lastMessageTime || (int) $this->lastMessageTime !== $last_message_time) {
			$this->setInfo('last_message_time', $last_message_time);

			$res = (object) array(
				'today' => $id_today,
				'tomorrow' => $id_tomorrow,
			);
		}

		return $res;
	}
	private function mailFetch($id) {
		// Fetch current plans
		$message = imap_fetchbody($this->mbox, $id, 2);

		if (!($html = base64_decode($message))) $html = $message;

		return $html;
	}
	public function updatePlan($from, $subject_today, $subject_tomorrow) {
		$info = $this->getInfo(array('last_message_time', 'last_check_time'));
		$this->lastMessageTime = (isset($info['last_message_time'])) ? $info['last_message_time'] : null;
		$this->lastCheckime = (isset($info['last_check_time'])) ? $info['last_check_time'] : null;

		if ($this->lastCheckime + self::$expire > time()) return false; // Cache

		$this->setInfo('last_check_time', time());

		$ids = $this->mailCheck($from, $subject_today, $subject_tomorrow);

		if (!$ids) return false; // No changes in mbox

		$html_today = $this->mailFetch($ids->today);
		$html_tomorrow = $this->mailFetch($ids->tomorrow);

		$plan = array();
		$this->parse($html_today, $plan);
		$this->parse($html_tomorrow, $plan);

		$this->save($plan);

		return true;
	}
	public function getPlanJSON($date, $class = null) {
		$plan = array();
		$data = array($date, $this->floorTimeLesson(time()));
		if ($class) $data[] = $class;
		$plan_q = $this->dbh->prepare(	'SELECT ' .
						'pid, time, class, subject, room, type, updated, description, date ' .
						'FROM plan p ' .
						'WHERE date = ? AND deleted = 0 AND time >= ? ' .
						($class ? 'AND class = ? ' : '') .
						'ORDER BY IF(CAST(SUBSTR(class, 1, 2) AS UNSIGNED) = 0, 9999, CAST(SUBSTR(class, 1, 2) AS UNSIGNED)) ASC, time ASC');
		$plan_q->execute($data);
		while ($pl = $plan_q->fetch(PDO::FETCH_ASSOC)) {
			$plan[] = $pl;
		}

		return json_encode(array('plan' => $plan, 'types' => $this->types, 'lessons' => $this->lessonTimes));
	}
	public function getPlanISC($class, $subjects) {
		$isc =	"BEGIN:VCALENDAR\n" .
			"VERSION:2.0\n" .
			"PRODID:-//hacksw/handcal//NONSGML v1.0//EN\n";

		$plan_q = $this->dbh->prepare(	'SELECT ' .
						'pid, time, class, subject, room, type, updated, description, date, deleted ' .
						'FROM plan p ' .
						'WHERE time >= ? AND class = ?' .
						((count($req_subjects) !== 0) ? ' AND subject IN(' . implode(',', array_fill(0, count($req_subjects), '?')) . ')' : '')
					);
		$plan_q->execute(array_merge(array($this->floorTimeLesson(time()), $class_name), $req_subjects));
		while ($pl = $plan_q->fetch(PDO::FETCH_OBJ)) {
			$uid = 'plan-' . $pl->pid . '@vplan.igsmz.net';
			$begin = gmdate('Ymd', $pl->time) . 'T' . gmdate('His', $pl->time) . 'Z';
			$end = gmdate('Ymd', $pl->time + 60 * 45) . 'T' . gmdate('His', $pl->time + 60 * 45) . 'Z';
			$type = ($types[$pl->type]->nice ? $types[$pl->type]->nice : $types[$pl->type]->name);
			if (!$type) $type = '-';

			$isc .=	"BEGIN:VEVENT\n" .
				"UID:" . $uid . "\n" .
				"DTSTAMP:" . $begin . "\n" .
				"DTSTART:" . $begin . "\n" .
				"DTEND:" . $end . "\n" .
				"SUMMARY:" . $pl->subject . " | " . $type . "\n" .
				"LOCATION:" . $pl->room . "\n" .
				"DESCRIPTION:" . $pl->description . "\n";

			if ($pl->deleted == 1) {
				$isc .=	"STATUS:CANCELLED\n";
			}
			$isc .=	"END:VEVENT\n";
		}
		$isc .=	"END:VCALENDAR\n";

		return $isc;
	}
	public function parse($html, &$dest) {
		require_once './lib/html5lib/library/HTML5/Parser.php';

		$html = str_replace('&nbsp;', '', $html); // Work around
		$dom = HTML5_Parser::parse($html);

		$info = new stdClass();
		foreach ($dom->getElementsByTagName('div') as $div) {
			$class = $div->getAttribute('class');

			if ($class !== 'mon_title') continue;

			$date = explode('.', current(explode(' ', trim(strip_tags($dom->saveHTML($div))))));

			$info->date = mktime(0, 0, 0, $date[1], $date[0], $date[2]);

			break;
		}
		foreach ($dom->getElementsByTagName('table') as $div) {
			$class = $div->getAttribute('class');

			if ($class !== 'mon_head') continue;

			preg_match('/stand:\s*([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})\s+([0-9]{1,2}):([0-9]{1,2})/i', $dom->saveHTML($div), $matches);

			$info->updated = mktime($matches[4], $matches[5], 0, $matches[2], $matches[1], $matches[3]);

			break;
		}

		foreach ($dom->getElementsByTagName('table') as $table) {
			$class = $table->getAttribute('class');

			if ($class !== 'mon_list') continue;

			// Plan
			$count = -1;
			foreach ($table->getElementsByTagName('tr') as $row) {
				++$count;
				if ($count === 0) continue; // The first row is the table head.

				$columns = array();
				foreach ($row->getElementsByTagName('td') as $column) {
					$column_content = strip_tags($dom->saveHTML($column));

					$columns[] = trim($column_content);
				}

				// 0       | 1       | 2       | 3    | 4    | 5
				// lessons | classes | subject | room | type | description

				// Lessons [0]
				$lessons = array();
				$raw_lessons = array_map('trim', explode(',', $columns[0]));
				foreach ($raw_lessons as $lesson) {
					if (!$lesson) continue;

					$range = array_map('intval', array_map('trim', explode('-', $lesson)));

					if (count($range) == 2) $lessons = array_merge($lessons, range($range[0], $range[1]));
					else $lessons[] = (int) $lesson;
				}

				// Classes [1]
				$classes = array();
				$raw_classes = array_map('trim', explode(',', $columns[1]));
				foreach ($raw_classes as $class) {
					if (!$class) continue;

					$classes[] = $class;
				}

				// Subject [2]
				$subject = $columns[2];

				// Room [3]
				$room = ($columns[3] === '---') ? null : $columns[3];

				// Type [4]
				$type = $columns[4];
				$tid = $this->tids[$type];
				// If the type doesn't exists
				if (!$tid) {
					$this->dbh->prepare('INSERT INTO type (name) VALUES (?)')->execute(array($type));
					$tid = $this->dbh->lastInsertId();
					$this->tids[$type] = $tid;
					$this->types[$tid] = (object) array('name' => $type, 'nice' => NULL);
				}

				// Description [5]
				$description = ($columns[5]) ? $columns[5] : null;

				// Continuation of the class column of the last row.
				if (isset($last) && count($lessons) == 0 && $subject == '' && $room == '' && $description == '') {
					list($lessons, $subject, $room, $tid, $description) = $last;
				}

				// Each class and lesson gets its own row.
				foreach ($classes as $class) {
					foreach ($lessons as $lesson) {
						$time = mktime(
							$this->lessonTimes[$lesson]['begin'][0], $this->lessonTimes[$lesson]['begin'][1], $this->lessonTimes[$lesson]['begin'][2],
							date('n', $info->date), date('j', $info->date), date('Y', $info->date)
						);

						if (!isset($dest[$class])) $dest[$class] = array();
						if (!isset($dest[$class][$subject])) $dest[$class][$subject] = array();
						$dest[$class][$subject][$time] = (object) array(
							'time' => $time,
							'class' => $class,
							'subject' => $subject,
							'room' => $room,
							'type' => $tid,
							'description' => $description,
							'updated' => $info->updated,
							'date' => $info->date,
						);
					}
				}

				$last = array($lessons, $subject, $room, $tid, $description);
			}

			break;
		}

		return $info;
	}
	public function save($plan) {
		$data = array();
		$classes = array();
		$subjects = array();
		$rooms = array();

		$dates = array();

		foreach ($plan as $subs) {
			foreach ($subs as $times) {
				foreach ($times as $row) {
					$data[] = $row->time;
					$data[] = $row->class;
					$data[] = $row->subject;
					$data[] = $row->room;
					$data[] = $row->type;
					$data[] = $row->updated;
					$data[] = $row->description;
					$data[] = $row->date;

					if (!in_array($row->class, $classes) && $row->class) $classes[] = $row->class;
					if (!in_array($row->subject, $subjects) && $row->subject) $subjects[] = $row->subject;
					if (!in_array($row->room, $rooms) && $row->room) $rooms[] = $row->room;

					if (!in_array($row->date, $dates) && $row->date) $dates[] = $row->date;
				}
			}
		}

		$insert_classes = array();
		foreach ($classes as $class) {
			$insert_classes[] = $class; // name
			preg_match('/^[0-9]+/', $class, $year_ref);
			$insert_classes[] = ($year_ref[0] === NULL) ? NULL : (int) $year_ref[0]; // year
		}
		$insert_subjects = $subjects;
		$insert_rooms = $rooms;

		if (count($insert_classes) / 2 !== 0) $this->dbh->prepare('INSERT IGNORE INTO class (class, year) VALUES ' . implode(',', array_fill(0, count($insert_classes) / 2, '(?, ?)')))->execute($insert_classes);
		if (count($insert_subjects) !== 0) $this->dbh->prepare('INSERT IGNORE INTO subject (subject) VALUES ' . implode(',', array_fill(0, count($insert_subjects), '(?)')))->execute($insert_subjects);
		if (count($insert_rooms) !== 0) $this->dbh->prepare('INSERT IGNORE INTO room (room) VALUES ' . implode(',', array_fill(0, count($insert_rooms), '(?)')))->execute($insert_rooms);

		if (count($dates) !== 0) {
			// Only future events can be deleted.
			$this->dbh->prepare('UPDATE plan SET deleted = 1 WHERE time > ? AND date IN(' . implode(',', array_fill(0, count($dates), '?')) . ')')->execute(array_merge(array(time()), $dates));

			$this->dbh->prepare('INSERT INTO plan (time, class, subject, room, type, updated, description, date) VALUES ' . implode(',', array_fill(0, count($data) / 8, '(?, ?, ?, ?, ?, ?, ?, ?)')) . ' ON DUPLICATE KEY UPDATE room = VALUES(room), type = VALUES(type), updated = VALUES(updated), description = VALUES(description), deleted = 0')->execute($data);
		}
	}

	public function getInfo($keys) {
		$info = array();
		$info_q = $this->dbh->prepare('SELECT * FROM info WHERE `key` IN(' . implode(',', array_fill(0, count($keys), '?')) . ')');
		$info_q->execute($keys);
		while ($inf = $info_q->fetch(PDO::FETCH_OBJ)) {
			$info[$inf->key] = $inf->value;
		}
		return $info;
	}
	public function setInfo($key, $value) {
		$this->dbh->prepare('INSERT INTO info (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')->execute(array($key, $value));
	}

	public function floorTimeLesson($time) {
		foreach ($this->lessonTimes as $lesson) {
			$time_lesson_begin = mktime($lesson['begin'][0], $lesson['begin'][1], $lesson['begin'][2], date('n', $time), date('j', $time), date('Y', $time));
			$time_lesson_end = mktime($lesson['end'][0], $lesson['end'][1], $lesson['end'][2], date('n', $time), date('j', $time), date('Y', $time));

			if ($time >= $time_lesson_begin && $time <= $time_lesson_end) return $time_lesson_begin; // It's the current lesson.
		}

		return $time;
	}
	public function ceilTimeLesson($time) {
		foreach ($this->lessonTimes as $lesson) {
			$time_lesson_begin = mktime($lesson['begin'][0], $lesson['begin'][1], $lesson['begin'][2], date('n', $time), date('j', $time), date('Y', $time));
			$time_lesson_end = mktime($lesson['end'][0], $lesson['end'][1], $lesson['end'][2], date('n', $time), date('j', $time), date('Y', $time));

			if ($time >= $time_lesson_begin && $time <= $time_lesson_end) return $time_lesson_end; // It's the current lesson.
		}

		return $time;
	}
}