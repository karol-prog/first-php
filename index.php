<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Students Attendance</title>
</head>
<body>
	<form action="index.php" method="post" >
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

	//times
	$schoolStart = new DateTime('08:00:00');
	$eveningStart = new DateTime('20:00:00');
	$midnight = new DateTime('23:59:59');

	//Files
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

	if (file_exists($fileForStudentsName)) {
		$getJsonData = file_get_contents($fileForStudentsName);
		$allStudentsArray = json_decode($getJsonData, true);
	} else {
		$allStudentsArray = [];
	}

	if (is_array($allStudentsArray)) {
		foreach ($allStudentsArray as &$value) {
			if ($value['name'] === $studentName) {
				$value['pocet prichodov']++;
				$studentExists = true;

				$sql = "SELECT * FROM studentsAttendence WHERE firstName = '$studentName'";
				$result = mysqli_query($connection, $sql);

				if (mysqli_num_rows($result) > 0) {
					// Student exists, update the numberOfAttendence
					$sql = "UPDATE studentsAttendence SET numberOfAttendence = numberOfAttendence + 1 WHERE firstName = '$studentName'";
					mysqli_query($connection, $sql);
					echo "Student attendance updated.<br>";
				} else {
					// Student doesn't exist, insert new student
					$sql = "INSERT INTO studentsAttendence (firstName, numberOfAttendence) VALUES ('$studentName', 1)";
					mysqli_query($connection, $sql);
					echo "New student added with attendance = 1.<br>";
				}

				break;
			}
		}
	} else {
		echo "Error: No student data available." . "<br>";
	}


	include_once "Classes/students.php";

	if (!$studentExists) {
		$newStudent = [
			"name" => $studentName,
			"pocet prichodov" => 1
		];
		LogStudents::putStudentToDatabase($newStudent);
		$allStudentsArray[] = $newStudent;
	}

	echo "Updated student data: <br>";
	foreach ($allStudentsArray as $student) {
		echo "Name: " . $student['name'] . " - Pocet prichodov: " . $student['pocet prichodov'] . "<br>";
	}

	print_r($allStudentsArray);

	//PRICHODY

	include_once "Classes/prichody.php";

	$prichody = [];
	foreach($allStudentsArray as $prichod) {
		if ($newDate > $schoolStart) {
			$prichody[] = new Prichody(
				$prichod['pocet prichodov'],
				"meskanie"
			);
		}

		$encodePrichody = json_encode($prichody, JSON_PRETTY_PRINT);
		file_put_contents($fileForPrichody, $encodePrichody);
	}
	print_r($prichody);

	function putTimesToFile($file, $times, $name) {
		global $message;
		global $schoolStart;
		global $eveningStart;
		global $midnight;

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

	putTimesToFile($fileForTimesLog, $formattedDate, $studentName);
	getAllTimesFromFile($fileForTimesLog);
}
