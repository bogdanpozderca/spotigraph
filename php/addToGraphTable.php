<?php
	session_start();
	$userID = $_SESSION['tableKey'];
    include_once('config.php');

    $userID = $_SESSION['tableKey'];

	$sql = "SELECT `COLUMN_NAME` 
		FROM `INFORMATION_SCHEMA`.`COLUMNS` 
		WHERE `TABLE_SCHEMA`='audiolyze' 
		AND `TABLE_NAME`='" . $userID . "_graphdata'";
	
	$columnResult = $conn->query($sql);
	mysqli_fetch_assoc($columnResult); //first is artist
	$dateArray = array();
	while ($columnRow = mysqli_fetch_assoc($columnResult)) {
		array_push($dateArray, $columnRow['COLUMN_NAME']);
	}

	$lastSongResult = $conn->query("SELECT lastSong FROM userData WHERE userID='$userID'");	
	$lastSongArray = mysqli_fetch_assoc($lastSongResult);
	$lastSong = $lastSongArray['lastSong'];

	$songResult = $conn->query("SELECT playID, publishTime, artistName FROM " 
		. $userID . "_musicdata ORDER BY publishTime DESC");

	$prevPlayDate = 'artist';
	while ($songRow = mysqli_fetch_assoc($songResult)) {
		if ($lastSong == $songRow['playID']) {break;} // song was already recorded in the graphDataTable, prevents duplicate.

		$songArtist = $songRow['artistName'];
		$totalResult = $conn->query("SELECT total FROM " 
			. $userID . "_graphdata WHERE artist='$songArtist'");
		$totalRow = mysqli_fetch_assoc($totalResult);
		$total = $totalRow['total'];

		$playDate = date("d-m-Y", strtotime($songRow['publishTime']));
		$dateResult = $conn->query("SELECT `" . $playDate 
			. "` FROM " . $userID . "_graphdata WHERE artist='$songArtist'");
		if (mysqli_num_rows($dateResult)) {
			$dateRow = mysqli_fetch_assoc($dateResult);
			$playsOnDate = $dateRow[$playDate];
			$playsOnDate++;
			$total++;
			$updateTable = "UPDATE " . $userID . "_graphdata SET `" . 
                    $playDate . "`='$playsOnDate', total='$total' WHERE artist='$songArtist'" ;
            $conn->query($updateTable);
		} else {
            $conn->query("ALTER TABLE " . $userID . "_graphdata ADD `" .
            	$playDate ."` MEDIUMINT NOT NULL DEFAULT '0' AFTER `" . $prevPlayDate . "`");
            $conn->query("UPDATE " . $userID . "_graphdata SET 
                `" . $playDate . "`=1 WHERE artist='$songArtist'");
            
            $total++;
			$updateTable = "UPDATE " . $userID .
				"_graphdata SET total='$total' WHERE artist='$songArtist'";
            $conn->query($updateTable);
            $prevPlayDate = $playDate;
		}
		$total = 0;
	}
	$conn->close();
?>