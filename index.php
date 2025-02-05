<?php

function get_connection(){
    $serverName = "youdatahost";  // Change this to your SQL Server instance
    $connectionOptions = array(
        "Database" => "test_database", // Change this to your database name
        "Uid" => "sa", // Change this to your SQL Server username
        "PWD" => "niraj123" // Change this to your SQL Server password
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if (!$conn) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $conn;
}

function save_event($month, $day, $time, $event){
    $conn = get_connection();

    $findSql = "SELECT id FROM my_timetable WHERE period = ? AND day = ? AND month = ?";
    $params = array($time, $day, $month);
    $query = sqlsrv_query($conn, $findSql, $params);

    if ($query === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    if ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
        $id = $row['id'];
        $updateSql = "UPDATE my_timetable SET event = ? WHERE id = ?";
        $params = array($event, $id);
        sqlsrv_query($conn, $updateSql, $params);
    } else {
        $insertSql = "INSERT INTO my_timetable (period, event, day, month, year) VALUES (?, ?, ?, ?, 2024)";
        $params = array($time, $event, $day, $month);
        sqlsrv_query($conn, $insertSql, $params);
    }
}

function get_events_by_month($month){
    $conn = get_connection();
    $sql = "SELECT * FROM my_timetable WHERE month = ?";
    $params = array($month);
    $query = sqlsrv_query($conn, $sql, $params);
    $results = [];

    while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
        $results[] = $row;
    }

    return $results;
}

function findEvent($events, $day, $time){
    foreach ($events as $event){
        if ($event["period"] == $time && $event["day"] == $day){
            return $event["event"];
        }
    }
    return false;
}

$months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];

$times = ['6:00-6:30AM', '6:30-7:00AM', '7:00-7:30AM', '7:30-8:00AM', '8:00-8:30AM', '8:30-9:00AM',
          '9:00-9:30AM', '9:30-10:00AM', '10:00-10:30AM', '10:30-11:00AM', '11:00-11:30AM',
          '11:30-12:00PM', '12:00-12:30PM', '12:30-1:00PM', '1:00-1:30PM', '1:30-2:00PM', '2:00-2:30PM'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save-event'])) {
    $event = $_POST['event'] ?? null;
    $month = $_POST['month'] ?? null;
    $day = $_POST['day'] ?? null;
    $time = $_POST['time'] ?? null;

    if ($event && $month != "--select--" && $day && $time) {
        save_event($month, $day, $time, $event);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Full-Year Timetable</title>
    <style>
        .tabs { display: flex; gap: 10px; }
        .tab { padding: 10px; background: #ff8000; cursor: pointer; border-radius: 5px; }
        .tab:hover { background: #ff5500; }
        .active-tab { background: #cc6600; color: white; }

        .day-header {
            height: 35px;
            background: #3788d8;
            text-align: center;
            padding: 5px;
            font-weight: bold;
        }

        .day-block {
            height: 35px;
            padding: 5px;
            background: #f6f6f6;
        }

        .timetable {
            border: 1px solid #3788d8;
            margin-top: 5px;
            border-collapse: collapse;
        }

        .timetable tr, td {
            border: 1px solid #3788d8;
        }

        .hidden { display: none; }
    </style>
</head>
<body>

<div class="tabs">
    <?php foreach ($months as $index => $month): ?>
        <div class="tab" onclick="showMonth('<?php echo $month; ?>')"><?php echo $month; ?></div>
    <?php endforeach; ?>
</div>

<form method="post">
    Event Name: <input name="event" type="text"/><br>
    Month:
    <select name="month">
        <option>--select--</option>
        <?php foreach ($months as $month): ?>
            <option><?php echo $month; ?></option>
        <?php endforeach; ?>
    </select><br>
    Date: <input type="number" name="day" min="1" max="31"/><br>
    Time:
    <select name="time">
        <option>--select--</option>
        <?php foreach ($times as $time): ?>
            <option><?php echo $time; ?></option>
        <?php endforeach; ?>
    </select><br>
    <button type="submit" name="save-event">Save Event</button>
</form>

<?php foreach ($months as $month): ?>
    <div id="<?php echo $month; ?>" class="hidden">
        <h2><?php echo $month; ?> Timetable</h2>
        <table style="width: 100%" class="timetable">
            <tr>
                <td class="day-header">Date</td>
                <?php foreach ($times as $time): ?>
                    <td class="day-header"><?php echo $time; ?></td>
                <?php endforeach; ?>
            </tr>
            <?php for ($day = 1; $day <= 31; $day++): ?>
                <tr>
                    <td class="day-block"><?php echo $day; ?></td>
                    <?php foreach ($times as $time): ?>
                        <td class="day-block"><?php echo findEvent(get_events_by_month($month), $day, $time) ?: "Free"; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>
<?php endforeach; ?>

<script>
    function showMonth(month) {
        document.querySelectorAll('.hidden').forEach(el => el.style.display = 'none');
        document.getElementById(month).style.display = 'block';
    }
</script>

</body>
</html>
