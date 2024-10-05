<?php
class LogStudents {
	public static function putStudentsToFile($file, $students) {
		$encodeAllStudents = json_encode($students, JSON_PRETTY_PRINT);
		file_put_contents($file, $encodeAllStudents);
	}
}
?>