<?php

$connection = include 'database.php';
class LogStudents {
	public static function putStudentToDatabase($newStudent) {
		print_r($newStudent);
		global $connection;

		$sql = "INSERT INTO studentsAttendence (firstName, numberOfAttendence) VALUES ('" . $newStudent['name'] . "', '" . $newStudent['pocet prichodov'] . "')";

		if ($connection->query($sql) === TRUE) {
			echo "New record created successfully";
		} else {
			echo "Error: " . $sql . "<br>" . $connection->error;
		}
	}
}

?>