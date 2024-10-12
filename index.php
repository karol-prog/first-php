<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Students Attendance</title>
</head>
<body>
	<form action="index.php" method="post">
		<label for="studentName">Student name:</label>
		<input type="text" name="studentName" placeholder="Student name" required>
		<button type="submit">Submit</button>
	</form>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
	$newDate = new DateTime();
	$formattedDate = $newDate->format("d-m-Y H:i:s");

	// Times
	$schoolStart = new DateTime('08:00:00');
	$eveningStart = new DateTime('20:00:00');
	$midnight = new DateTime('23:59:59');

	// Files
	$fileForTimesLog = "times.txt";
	$fileForStudentsName = "studenti.json";
	$fileForPrichody = "prichody.json";

	$message = " - You are late";

	$studentName = isset($_POST["studentName"]) ? $_POST["studentName"] : (isset($_GET["studentName"]) ? $_GET["studentName"] : null);
	$studentExists = false;

	$connection = include 'database.php';

	if (!$studentName) {
		die("Student name is required.");
	}

	$studentId = null;
	$studentQuery = "SELECT id FROM studentsAttendence WHERE firstName = '$studentName' LIMIT 1";
	$result = mysqli_query($connection, $studentQuery);

	if (mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_assoc($result);
		$studentId = $row['id'];
	}

	if (!$studentId) {
		// If student not found, create a new student entry in DB and get the new id
		$studentQuery = "INSERT INTO studentsAttendence (firstName, numberOfAttendence) VALUES ('$studentName', 1)";
		mysqli_query($connection, $studentQuery);
		$studentId = mysqli_insert_id($connection);  // Get the new student's id
		echo "New student added with id = $studentId and attendance = 1.<br>";
	}

	if (file_exists($fileForStudentsName)) {
		$getJsonData = file_get_contents($fileForStudentsName);
		$allStudentsArray = json_decode($getJsonData, true);
	} else {
		$allStudentsArray = [];
	}

	if (is_array($allStudentsArray)) {
		foreach ($allStudentsArray as &$value) {
			if ($value['id'] == $studentId) {
				$value['pocet prichodov']++;
				$studentExists = true;

				$studentQuery = "UPDATE studentsAttendence SET numberOfAttendence = numberOfAttendence + 1 WHERE id = '$studentId'";

				if (mysqli_query($connection, $studentQuery)) {
					echo "Student attendance updated.<br>";
				} else {
					echo "Error: " . mysqli_error($connection) . "<br>";
				}

				break;
			}
		}
	}

	if (!$studentExists) {
		$newStudent = [
			"id" => $studentId,
			"name" => $studentName,
			"pocet prichodov" => 1
		];
		$allStudentsArray[] = $newStudent;
	}

	file_put_contents($fileForStudentsName, json_encode($allStudentsArray, JSON_PRETTY_PRINT));

	foreach ($allStudentsArray as $student) {
		echo "Name: " . $student['name'] . " - Pocet prichodov: " . $student['pocet prichodov'] . "<br>";
	}

	include_once "Classes/prichody.php";

	$prichody = [];
	foreach($allStudentsArray as $prichod) {
		if ($newDate > $schoolStart) {
			$prichody[] = new Prichody(
				$prichod['pocet prichodov'],
				"meskanie"
			);

			// Mark student as late
			$sqlQuery = "UPDATE studentsAttendence SET wasLate = 1 WHERE id = '$studentId'";
			mysqli_query($connection, $sqlQuery);
			echo "Student marked as late.<br>";
		}

		$encodePrichody = json_encode($prichody, JSON_PRETTY_PRINT);
		file_put_contents($fileForPrichody, $encodePrichody);
	}

	putTimesToFile($fileForTimesLog, $formattedDate, $studentName);
	getAllTimesFromFile($fileForTimesLog);
}

function putTimesToFile($file, $times, $name) {
	global $message, $schoolStart, $eveningStart, $midnight;

	$currentTime = new DateTime($times);

	if ($currentTime >= $eveningStart && $currentTime <= $midnight) {
		die("Attendance cannot be recorded after 8 PM.");
	} else if ($currentTime > $schoolStart) {
		$entry = "$times - $name$message";
	} else {
		$entry = "$times - $name";
	}

	file_put_contents($file, $entry . "\n", FILE_APPEND);
}

function getAllTimesFromFile($file) {
	$allFileTimes = file_get_contents($file);
	echo "This is the all times in the times.txt:<br><pre>{$allFileTimes}</pre>";
}
