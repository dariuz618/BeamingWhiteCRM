<!DOCTYPE html>
<html>
<head>
</head>
<body>

<table>
	<tr>
		<th>ID</th>
		<th>Name</th>
		<th>City</th>
		<th>State</th>
		<th>Country</th>
		<th>Begins</th>
		<th>Ends</th>
		<th>Year</th>
	</tr>
	<?php
		// Database Connection
		require('config.php');
		
		// Global Variables from _GET
		$name = strval($_GET['name']);
		$city = strval($_GET['city']);
		$year = strval($_GET['year']);
		
		// Output Check
		echo "<h3>" . $name . " in " . $city . " during " . $year . "</h3>";

		// Check Variables and Choose MySQL Query to Send to Server
		if (isset($name) && !isset($city) && !isset($year)) {
			$sql = 'SELECT * FROM tradeshows GROUP BY name ORDER BY name asc;';
		}
		elseif (isset($name) && isset($city) && !isset($year)) {
			$sql = "SELECT * FROM tradeshows WHERE name LIKE '$name' GROUP BY city ORDER BY city asc;";
		}
		elseif (isset($name) && isset($city) && isset($year)) {
			$sql = "SELECT * FROM tradeshows WHERE name = '$name' AND city = '$city' AND year = '$year';";
		} 
		else {
			echo "Oh no! Something went wrong. Please contact your server administrator.";
		}

		// Output Each Results as a Row
	  foreach ($conn->query($sql) as $row) {
			echo "<tr>";
			echo "<td>" . $row['id'] . "\t";
			echo "<td>" . $row['name'] . "\t";
			echo "<td>" . $row['city'] . "\n";
			echo "<td>" . $row['state'] . "\n";
			echo "<td>" . $row['country'] . "\n";	
			echo "<td>" . $row['datebegins'] . "\n";	
			echo "<td>" . $row['dateends'] . "\n";
			echo "<td>" . $row['year'] . "\n";
			echo "</tr>";
	  }
		
		// End MySQL Connection
		$conn = null;
	?>
</table>
</body>
</html>