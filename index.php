<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Students Attendance</title>
</head>
<body>
	<form action="index.php" method="get" >
		<label for="student_name">Student name:</label>
		<input type="text" name="student_name" placeholder="Student name" required>
		<button type="submit">Submit</button>
	</form>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
	$newDate = new DateTime();
	$formatted_date = $newDate->format("d-m-Y H:i:s");

	$school_start = new DateTime('08:00:00');
	$evening_start = new DateTime('20:00:00');
	$midnight = new DateTime('23:59:59');

	$file_for_times_log = "times.txt";
	$file_for_students_name = "studenti.json";
	$file_for_prichody = "prichody.json";

	$message = " - You are late";

	$student_name = ($_SERVER["REQUEST_METHOD"] === "POST") ? $_POST["student_name"] : $_GET["student_name"];
	$student_exists = false;

	if (file_exists($file_for_students_name)) {
		$get_json_data = file_get_contents($file_for_students_name);
		$all_students_array = json_decode($get_json_data, true);
	} else {
		$all_students_array = [];
	}

	foreach ($all_students_array as &$value) {
		if ($value['name'] === $student_name) {
			$value['pocet prichodov']++;
			$student_exists = true;
			break;
		}
	}

	if (!$student_exists) {
		$new_student = [
			"name" => $student_name,
			"pocet prichodov" => 1
		];
		$all_students_array[] = $new_student;
	}

	//update json
	$encode_all_students = json_encode($all_students_array, JSON_PRETTY_PRINT);
	file_put_contents($file_for_students_name, $encode_all_students);

	echo "Updated student data: <br>";
	foreach ($all_students_array as $student) {
		echo "Name: " . $student['name'] . " - Pocet prichodov: " . $student['pocet prichodov'] . "<br>";
	}

	//PRICHODY
	print_r($all_students_array);
	$prichody = [];
	foreach($all_students_array as $prichod) {
		$prichody[] = $prichod['pocet prichodov'];
		$encode_prichody = json_encode($prichody, JSON_PRETTY_PRINT);
		file_put_contents($file_for_prichody, $encode_prichody);
	}
	print_r($prichody);

	function putTimesToFile($file, $times, $name) {
		global $message;
		global $school_start;
		global $evening_start;
		global $midnight;

		$current_time = new DateTime($times);

		if ($current_time >= $evening_start && $current_time <= $midnight) {
			die("Attendance cannot be recorded after 8 PM.");
		} else if ($current_time > $school_start) {
			$entry = "$times - $name$message";
		} else {
			$entry = "$times - $name";
		}

		file_put_contents($file, $entry . "\n", FILE_APPEND);
	}

	function getAllTimesFromFile($file) {
		$all_file_times = file_get_contents($file);
		echo "This is the all times in the times.txt:<br><pre>{$all_file_times}</pre>";
	}

	putTimesToFile($file_for_times_log, $formatted_date, $student_name);
	getAllTimesFromFile($file_for_times_log);
}
