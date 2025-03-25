<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['event_id'])) {
    exit('Invalid request');
}

$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$_GET['event_id']]);
$event = $stmt->fetch();

// Generate iCal content
$ical = "BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SUMMARY:" . $event['title'] . "
DTSTART:" . date('Ymd\THis', strtotime($event['date'])) . "
LOCATION:" . $event['location'] . "
DESCRIPTION:" . $event['description'] . "
END:VEVENT
END:VCALENDAR";

// Output calendar file
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=event.ics');
echo $ical;