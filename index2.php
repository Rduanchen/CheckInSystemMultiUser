<?php
    $db = new SQLite3('db.sqlite');
    date_default_timezone_set('Asia/Taipei');
    $current_time = date('Y-m-d H:i:s');
    session_start();
    if (isset($_SESSION['username']) && isset($_COOKIE['username']) && isset($_GET['action'])) {
        $username = $_SESSION['username'];
        $action = $_GET['action'];
        // echo "The messeage you sent is" . $_GET['ip'] . $_GET['longitude'] . $_GET['latitude'];
        if ($action == 'CheckIn') {
            $stmt = $db->prepare('INSERT INTO records (username, time, action, ip, longitude, latitude, location) VALUES (:username, :time, :action, :ip, :longitude, :latitude, :location)');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':time', $current_time, SQLITE3_TEXT);
            $stmt->bindValue(':action', 'CheckIn', SQLITE3_TEXT);
            $stmt->bindValue(':ip', $_GET['ip'], SQLITE3_TEXT);
            $stmt->bindValue(':longitude', $_GET['longitude'], SQLITE3_FLOAT);
            $stmt->bindValue(':latitude', $_GET['latitude'], SQLITE3_FLOAT);
            if ($_GET['latitude'] > 21.991 && $_GET['latitude'] < 21.992 && $_GET['longitude'] < 120.217 && $_GET['longitude'] > 120.218){
                $stmt->bindValue(':location', 'In PTWA', SQLITE3_TEXT);
            } else {
                $stmt->bindValue(':location', 'Out PTWA', SQLITE3_TEXT);
            }
            $stmt->execute();
            header('Content-Type: html/text');
            echo "CheckIn recorded at $current_time";
        } elseif ($action == 'CheckOut') {
            $stmt = $db->prepare('INSERT INTO records (username, time, action, ip, longitude, latitude, location) VALUES (:username, :time, :action, :ip, :longitude, :latitude, :location)');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':time', $current_time, SQLITE3_TEXT);
            $stmt->bindValue(':action', 'CheckOut', SQLITE3_TEXT);
            $stmt->bindValue(':ip', $_GET['ip'], SQLITE3_TEXT);
            $stmt->bindValue(':longitude', $_GET['longitude'], SQLITE3_FLOAT);
            $stmt->bindValue(':latitude', $_GET['latitude'], SQLITE3_FLOAT);
            if ($_GET['latitude'] > 21.991 && $_GET['latitude'] < 21.992 && $_GET['longitude'] < 120.217 && $_GET['longitude'] > 120.218){
                $stmt->bindValue(':location', 'In PTWA', SQLITE3_TEXT);
            } else {
                $stmt->bindValue(':location', 'Out PTWA', SQLITE3_TEXT);
            }
            header('Content-Type: html/text');
            echo "CheckOut recorded at $current_time";
        } elseif ($action == 'Read') {
            $stmt = $db->prepare('SELECT * FROM records WHERE username = :username');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $result = $stmt->execute();
            $records = [];
            while ($row = $result->fetchArray()) {
                $records[] = $row;
            }
            if ($records) {
                header('Content-Type: html/text');
                echo '<table border="1">';
                echo '<tr><th>Time</th><th>Action</th></tr>';
                foreach ($records as $record) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($record['time']) . '</td>';
                    echo '<td>' . htmlspecialchars($record['action']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '<br><br/><a href="./download.php">Download CSV</a>';
            } else {
                echo 'No records found.';
            }
        } elseif ($action == 'GetInfo') {
            $stmt = $db->prepare('SELECT time, action FROM records WHERE username = :username');
            if (!$stmt) {
                echo json_encode(['error' => 'Failed to prepare statement: ' . $db->lastErrorMsg()]);
                exit;
            }
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $result = $stmt->execute();
            if (!$result) {
                echo json_encode(['error' => 'Failed to execute statement: ' . $db->lastErrorMsg()]);
                exit;
            }
            $records = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $records[] = [$row['time'], $row['action']];
            }
            header('Content-Type: application/json');
            echo json_encode($records);
        }
    } else {
        echo 'Please login first.';
    }
?>
            