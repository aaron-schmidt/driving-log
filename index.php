<?
	// http://www.ird.govt.nz/business-income-tax/expenses/vehicle-exp/

	// Get log data
	$log = array();
	$file = './log/drive.log';
	if (is_file($file)) {
		$log = unserialize(file_get_contents($file));
	}

//	echo "<pre>";
//	print_r($log);

	file_put_contents($file, serialize($log));

	// Process post request
	$error = null;
	if (!empty($_POST))
	{
		if (!is_numeric($_POST['odometer']) || strlen($_POST['odometer']) != 5) {
			$error = "Odometer must be 5 digit number";
		}

		if (empty($error)) {
			$_POST['time'] = time();
			array_push($log, $_POST);
			file_put_contents($file, serialize($log));
			file_put_contents(str_replace('.log', date('.Y-m-d') . '.log', $file), serialize($log));
		}
		else {
			$error = "<p class='notify'>$error</p>";
		}
	}

	// Get most recent value
	$last = end($log);

	// Build from/to select lists
	$fromList = $toList = null;
	$destinationList = array('Home','DDC','Wheelers','MyHR','Lawyer','Personal','other');
	foreach($destinationList as $dest) {
		$fromSelected = ($last && $last['to'] == $dest) ? " selected" : null;
		$fromList .= "<option value='$dest'$fromSelected>$dest</option>";
		$toSelected = ($last && $last['to'] == 'Home' && $dest == 'DDC') ? " selected" : null;
		$toList .= "<option value='$dest'$toSelected>$dest</option>";
	}

	$total = $log[count($log) - 1]['odometer'] - $log[0]['odometer'];
	$personal = 0;
	foreach ($log as $item) {
		if (!empty($prev) && $item['from'] == 'Personal' || $item['to'] == 'Personal') {
			$personal += ($item['odometer'] - $prev['odometer']);
		}
		$prev = $item;
	}

	date_default_timezone_set('NZ');
?>
<!DOCTYPE html>
<html>

<head>
	<title>Driving Log</title>
	<meta name='viewport' content='width=device-width, initial-scale=1.0' />
	<link rel='stylesheet' href='./css/drive.css'>
</head>

<body>

<?= $error ?>
<form method='post' action='/drive/'>
	<select name='from'><?= $fromList ?></select> <span>to</span>
	<select name='to'><?= $toList ?></select><br>
	<input type='tel' pattern='[0-9]{5}' name='odometer' placeholder='Odometer' maxlength='5' value='<?= $last ? substr($last['odometer'], 0, 3) : null ?>'>
	<button>Save</button>
</form>

<table>
	<tr>
		<th>Date</th>
		<th>From</th>
		<th>To</th>
		<th>Odometer</th>
	</tr>
<?
foreach (array_reverse($log) as $item) {
	echo "<tr>\n";
	echo "	<td>" . date("M j", $item['time']) . "</td>\n";
	echo "	<td>{$item['from']}</td>\n";
	echo "	<td>{$item['to']}</td>\n";
	echo "	<td>" . number_format($item['odometer']) . "</td>\n";
	echo "</tr>\n";
}
?>
</table>

<p>Driven: <?= number_format($total) ?>km (<?= number_format($personal / $total * 100) ?>% personal)</p>

</body>

</html>