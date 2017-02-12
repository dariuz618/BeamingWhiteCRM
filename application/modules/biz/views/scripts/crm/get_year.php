<!DOCTYPE html>
<html>
<head></head>
<body>
<form>	
	Select Year:
	<select name="shows" onchange="dataSet(this.value)">
		<option>Please select a year...</option>
			<?php
				// Database Connection
				require('config.php');
				
				// Global Variables from _GET
				$name = strval($_GET['name']);
				$city = strval($_GET['city']);
				
				// MySQL Query
			  $sql = "SELECT * FROM tradeshows WHERE name = '$name' AND city = '$city' GROUP BY year ORDER BY year asc;";
				
				// Output Each MySQL Result in Dropdown Menu
			  $i = 0;
				foreach ($conn->query($sql) as $row) {
					echo "<option value='$row[year]'>$row[year]</option>";
					$i++;
			  }
			?>
	</select>
</form>
</body>
</html>