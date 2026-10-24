<?php
session_start();

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include "../db.php";

/*
|--------------------------------------------------------------------------
| DATABASE TIMEZONE
|--------------------------------------------------------------------------
*/
mysqli_query($conn, "SET time_zone = '+08:00'");


/*
|--------------------------------------------------------------------------
| GET VEHICLES
|--------------------------------------------------------------------------
*/
$result = mysqli_query(
    $conn,
    "SELECT * FROM vehicles ORDER BY id DESC"
);

$totalCars = $result ? mysqli_num_rows($result) : 0;
$occupied = $totalCars;
$available = max(0, 50 - $occupied);

/*
|--------------------------------------------------------------------------
| RESERVATIONS
|--------------------------------------------------------------------------
*/
$pendingQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS cnt
     FROM reservations
     WHERE status='pending'"
);

$pendingCount = $pendingQuery
    ? (mysqli_fetch_assoc($pendingQuery)['cnt'] ?? 0)
    : 0;

$approvedQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS cnt
     FROM reservations
     WHERE status='approved'"
);

$approvedCount = $approvedQuery
    ? (mysqli_fetch_assoc($approvedQuery)['cnt'] ?? 0)
    : 0;

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/
$usersQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS cnt
     FROM users
     WHERE role='user'"
);

$usersCount = $usersQuery
    ? (mysqli_fetch_assoc($usersQuery)['cnt'] ?? 0)
    : 0;

/*
|--------------------------------------------------------------------------
| EXIT REQUESTS
|--------------------------------------------------------------------------
*/
$urgentQ = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS cnt
     FROM parking_sessions
     WHERE status='active'
     AND exit_requested=1"
);

$urgentCount = $urgentQ
    ? (mysqli_fetch_assoc($urgentQ)['cnt'] ?? 0)
    : 0;

/*
|--------------------------------------------------------------------------
| OVERTIME
|--------------------------------------------------------------------------
*/
$overtimeQ = mysqli_query(
    $conn,
    "SELECT time_in, duration_hours
     FROM parking_sessions
     WHERE status='active'"
);

$overtimeCount = 0;

if ($overtimeQ) {

    while ($o = mysqli_fetch_assoc($overtimeQ)) {

        $elapsedQ = mysqli_query(
            $conn,
            "SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, '{$o['time_in']}', NOW())) AS elapsed"
        );

        $elapsedData = $elapsedQ
            ? mysqli_fetch_assoc($elapsedQ)
            : null;

        $elapsed = (int)($elapsedData['elapsed'] ?? 0);

        $plannedSeconds =
            ((int)$o['duration_hours']) * 3600;

        $grace = 15 * 60;

        if ($elapsed > ($plannedSeconds + $grace)) {
            $overtimeCount++;
        }
    }
}

/*
|--------------------------------------------------------------------------
| TODAY'S REVENUE
|--------------------------------------------------------------------------
*/
$todayRevQ = mysqli_query(
    $conn,
    "SELECT SUM(total_fee) AS rev
     FROM history
     WHERE DATE(time_out) = CURDATE()"
);

$todayRev = $todayRevQ
    ? (mysqli_fetch_assoc($todayRevQ)['rev'] ?? 0)
    : 0;

/*
|--------------------------------------------------------------------------
| MONTHLY REVENUE
|--------------------------------------------------------------------------
*/
$monthRevQ = mysqli_query(
    $conn,
    "SELECT SUM(total_fee) AS rev
     FROM history
     WHERE MONTH(time_out) = MONTH(CURDATE())
     AND YEAR(time_out) = YEAR(CURDATE())"
);

$monthRev = $monthRevQ
    ? (mysqli_fetch_assoc($monthRevQ)['rev'] ?? 0)
    : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Hypercar Parking HUD</title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link
        rel="stylesheet"
        href="style.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    >

</head>

<body>
    

<div class="container">

    <!-- =========================================================
         TITLE
    ========================================================== -->

    <h1 class="title">
        ⚡ ⚡ HYPERCAR PARKING HUD
    </h1>


    <!-- =========================================================
         NAVIGATION
    ========================================================== -->

    <div class="nav">

        <a href="history.php">
            History
        </a>

        <a href="revenue.php">
            Revenue
        </a>

        <a href="manage_users.php">
            Users
        </a>

        <a href="manage_reservations.php">

            Reservations

            <?php if ($pendingCount > 0): ?>

                <span
                    style="
                        background:red;
                        padding:2px 8px;
                        border-radius:20px;
                        font-size:12px;
                    "
                >
                    <?php echo (int)$pendingCount; ?>
                </span>

            <?php endif; ?>

        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>


    <!-- =========================================================
         ALERTS
    ========================================================== -->

    <?php if ($urgentCount > 0 || $overtimeCount > 0): ?>

        <div class="admin-alerts">

            <?php if ($urgentCount > 0): ?>

                <div class="alert-item urgent">

                    <i class="fas fa-door-open"></i>

                    <strong>
                        <?php echo (int)$urgentCount; ?>
                    </strong>

                    customer<?php echo $urgentCount > 1 ? 's' : ''; ?>
                    waiting to leave

                </div>

            <?php endif; ?>


            <?php if ($overtimeCount > 0): ?>

                <div class="alert-item overtime">

                    <i class="fas fa-exclamation-triangle"></i>

                    <strong>
                        <?php echo (int)$overtimeCount; ?>
                    </strong>

                    vehicle<?php echo $overtimeCount > 1 ? 's' : ''; ?>
                    overtime

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         STAT CARDS
    ========================================================== -->

    <div class="cards">

        <div class="card">

            <h3>
                Total Cars
            </h3>

            <p
                class="counter"
                data-target="<?php echo (int)$totalCars; ?>"
            >
                0
            </p>

        </div>


        <div class="card">

            <h3>
                Occupied Slots
            </h3>

            <p
                class="counter"
                data-target="<?php echo (int)$occupied; ?>"
            >
                0
            </p>

        </div>


        <div class="card">

            <h3>
                Available Slots
            </h3>

            <p
                class="counter"
                data-target="<?php echo (int)$available; ?>"
            >
                0
            </p>

        </div>


        <div class="card">

            <h3>
                Registered Users
            </h3>

            <p
                class="counter"
                data-target="<?php echo (int)$usersCount; ?>"
            >
                0
            </p>

        </div>

    </div>


    <!-- =========================================================
         QUICK ACTIONS
    ========================================================== -->

    <h2 class="section">
        QUICK ACTIONS
    </h2>


    <div class="quick-panel">


        <!-- SCAN -->

        <a
            href="scan.php"
            class="quick-tile scan-tile"
        >

            <div class="quick-icon">

                <i class="fas fa-qrcode"></i>

            </div>

            <div class="quick-info">

                <h4>
                    Scan QR
                </h4>

                <p>
                    Check-in with QR code
                </p>

            </div>

        </a>


        <!-- RESERVATIONS -->

        <a
            href="manage_reservations.php"
            class="quick-tile reserve-tile"
        >

            <div class="quick-icon">

                <i class="fas fa-calendar-check"></i>

            </div>

            <div class="quick-info">

                <h4>
                    Reservations
                </h4>

                <p>
                    <?php echo (int)$pendingCount; ?>
                    pending /
                    <?php echo (int)$approvedCount; ?>
                    approved
                </p>

            </div>


            <?php if ($pendingCount > 0): ?>

                <span class="quick-badge">

                    <?php echo (int)$pendingCount; ?>

                </span>

            <?php endif; ?>

        </a>


        <!-- TODAY -->

        <a
            href="revenue.php"
            class="quick-tile revenue-tile"
        >

            <div class="quick-icon">

                <i class="fas fa-chart-line"></i>

            </div>

            <div class="quick-info">

                <h4>
                    Today's Revenue
                </h4>

                <p>
                    ₱<?php echo number_format((float)$todayRev, 2); ?>
                </p>

            </div>

        </a>


        <!-- MONTH -->

        <a
            href="revenue.php"
            class="quick-tile money-tile"
        >

            <div class="quick-icon">

                <i class="fas fa-money-bill-wave"></i>

            </div>

            <div class="quick-info">

                <h4>
                    This Month
                </h4>

                <p>
                    ₱<?php echo number_format((float)$monthRev, 2); ?>
                </p>

            </div>

        </a>


    </div>


    <!-- =========================================================
         ACTIVE VEHICLES
    ========================================================== -->

    <h2 class="section">
        ACTIVE VEHICLES
    </h2>


    <input
        type="text"
        id="searchBar"
        placeholder="Search Plate or Owner"
    >


    <div class="table-container">

        <table id="vehicleTable">

            <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        Plate Number
                    </th>

                    <th>
                        Owner
                    </th>

                    <th>
                        Type
                    </th>

                    <th>
                        Slot
                    </th>

                    <th>
                        Time Parked
                    </th>

                    <th>
                        Fee
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php

            if ($result) {

                mysqli_data_seek($result, 0);

                while ($row = mysqli_fetch_assoc($result)) {

                    $plate =
                        mysqli_real_escape_string(
                            $conn,
                            $row['plate_number']
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | GET ACTIVE PARKING SESSION
                    |--------------------------------------------------------------------------
                    */

                    $sessQ = mysqli_query(
                        $conn,
                        "
                        SELECT *
                        FROM parking_sessions
                        WHERE plate_number='$plate'
                        AND status='active'
                        ORDER BY id DESC
                        LIMIT 1
                        "
                    );


                    $sess =
                        $sessQ
                        ? mysqli_fetch_assoc($sessQ)
                        : null;


                    /*
                    |--------------------------------------------------------------------------
                    | DEFAULT VALUES
                    |--------------------------------------------------------------------------
                    */

                    $hasOvertime = false;

                    $overtimeHours = 0;

                    $overtimeFee = 0;

                    $exitRequested = false;

                    $baseFee = 0;


                    /*
                    |--------------------------------------------------------------------------
                    | GET START TIME
                    |--------------------------------------------------------------------------
                    */

                    if ($sess) {

                        $startDateTime =
                            $sess['time_in'];

                    } else {

                        $startDateTime =
                            $row['time_in'] ?? '';

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CALCULATE ELAPSED TIME USING MYSQL
                    |--------------------------------------------------------------------------
                    */

                    $elapsedAtLoad = 0;

                    if (!empty($startDateTime)) {

                        $safeStart = mysqli_real_escape_string(
                            $conn,
                            $startDateTime
                        );

                        $elapsedQ = mysqli_query(
                            $conn,
                            "SELECT GREATEST(0, TIMESTAMPDIFF(SECOND, '$safeStart', NOW())) AS elapsed"
                        );

                        if ($elapsedQ) {
                            $elapsedData = mysqli_fetch_assoc($elapsedQ);
                            $elapsedAtLoad = max(
                                0,
                                (int)($elapsedData['elapsed'] ?? 0)
                            );
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PARKING SESSION DATA
                    |--------------------------------------------------------------------------
                    */

                    if ($sess) {

                        $baseFee =
                            (float)(
                                $sess['total_fee'] ?? 0
                            );


                        $plannedSeconds =
                            ((int)(
                                $sess['duration_hours'] ?? 0
                            )) * 3600;

                        $elapsedSeconds = $elapsedAtLoad;


                        $graceSeconds =
                            15 * 60;


                        $overtimeSeconds =
                            max(
                                0,
                                $elapsedSeconds
                                - $plannedSeconds
                                - $graceSeconds
                            );


                        $overtimeHours =
                            (int)ceil(
                                $overtimeSeconds / 3600
                            );


                        $overtimeFee =
                            $overtimeHours * 20;


                        $hasOvertime =
                            $overtimeHours > 0;


                        $exitRequested =
                            !empty(
                                $sess['exit_requested']
                            );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VEHICLE SOURCE
                    |--------------------------------------------------------------------------
                    */

                    $source =
                        $row['source'] ?? 'walk-in';


                    $sourceLabel =
                        'Walk-in';


                    $sourceClass =
                        'src-walkin';


                    if ($source === 'user') {

                        $sourceLabel =
                            'Customer';

                        $sourceClass =
                            'src-user';

                    } elseif ($source === 'reservation') {

                        $sourceLabel =
                            'Reserved';

                        $sourceClass =
                            'src-reservation';

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL FEE
                    |--------------------------------------------------------------------------
                    */

                    $totalDue =
                        $baseFee + $overtimeFee;

                    ?>

                    <tr
                        class="<?php
                            echo $exitRequested
                                ? 'row-exit-request'
                                : (
                                    $hasOvertime
                                    ? 'row-overtime'
                                    : ''
                                );
                        ?>"
                    >

                        <!-- ID -->

                        <td>

                            <?php
                            echo (int)$row['id'];
                            ?>

                        </td>


                        <!-- PLATE -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['plate_number'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>


                            <?php if ($exitRequested): ?>

                                <span class="exit-badge">

                                    <i class="fas fa-door-open"></i>

                                    LEAVING

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- OWNER -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $row['owner_name'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </td>


                        <!-- TYPE -->

                        <td>

                            <span
                                class="source-badge <?php
                                    echo htmlspecialchars(
                                        $sourceClass,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>"
                            >

                                <i
                                    class="fas fa-<?php

                                        echo $source === 'walk-in'
                                            ? 'walking'
                                            : (
                                                $source === 'user'
                                                ? 'user'
                                                : 'calendar-check'
                                            );

                                    ?>"
                                ></i>


                                <?php
                                echo htmlspecialchars(
                                    $sourceLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </span>

                        </td>


                        <!-- SLOT -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                (string)(
                                    $row['slot_number'] ?? ''
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>

                        </td>


                        <!-- =================================================
                             LIVE TIMER
                        ================================================== -->

                        <td
                            class="timer"
                            data-elapsed="<?php echo (int)$elapsedAtLoad; ?>"
                        >
                            <?php
                                $timerHours = floor($elapsedAtLoad / 3600);
                                $timerMinutes = floor(($elapsedAtLoad % 3600) / 60);
                                $timerSeconds = $elapsedAtLoad % 60;

                                echo $timerHours . 'h '
                                   . $timerMinutes . 'm '
                                   . $timerSeconds . 's';
                            ?>
                        </td>


                        <!-- FEE -->

                        <td class="fee">

                            <?php if ($sess): ?>

                                <?php if ($hasOvertime): ?>

                                    <span class="fee-base">

                                        ₱<?php
                                        echo number_format(
                                            $baseFee,
                                            2
                                        );
                                        ?>

                                    </span>


                                    <span class="fee-overtime">

                                        +₱<?php
                                        echo number_format(
                                            $overtimeFee,
                                            2
                                        );
                                        ?>
                                        OT

                                    </span>


                                    <div class="fee-total">

                                        ₱<?php
                                        echo number_format(
                                            $totalDue,
                                            2
                                        );
                                        ?>

                                    </div>

                                <?php else: ?>

                                    ₱<?php
                                    echo number_format(
                                        $baseFee,
                                        2
                                    );
                                    ?>

                                <?php endif; ?>

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </td>


                        <!-- ACTION -->

                        <td>

                            <a
                                class="editbtn"
                                href="edit.php?id=<?php
                                    echo (int)$row['id'];
                                ?>"
                            >
                                EDIT
                            </a>


                            <a
                                class="exitbtn <?php
                                    echo $exitRequested
                                        ? 'urgent'
                                        : '';
                                ?>"
                                href="exit.php?exit_id=<?php
                                    echo (int)$row['id'];
                                ?>"
                            >

                                EXIT<?php
                                    echo $exitRequested
                                        ? ' ⚠'
                                        : '';
                                ?>

                            </a>

                        </td>

                    </tr>

                    <?php

                }

            }

            ?>

            </tbody>

        </table>

    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================== -->

<script>
(function () {

    /* =========================================================
       DASHBOARD COUNTERS
    ========================================================= */

    const counters = document.querySelectorAll('.counter');

    counters.forEach(function (counter) {

        const target = Number(
            counter.getAttribute('data-target')
        );

        let count = 0;

        function updateCounter() {

            if (count < target) {
                count++;
                counter.textContent = count;
                setTimeout(updateCounter, 20);
            } else {
                counter.textContent = target;
            }
        }

        updateCounter();
    });


    /* =========================================================
       SEARCH ACTIVE VEHICLES
    ========================================================= */

    const searchBar = document.getElementById('searchBar');

    if (searchBar) {

        searchBar.addEventListener('input', function () {

            const value = this.value
                .toLowerCase()
                .trim();

            const rows = document.querySelectorAll(
                '#vehicleTable tbody tr'
            );

            rows.forEach(function (row) {

                const plate = row.children[1]
                    ? row.children[1].textContent.toLowerCase()
                    : '';

                const owner = row.children[2]
                    ? row.children[2].textContent.toLowerCase()
                    : '';

                row.style.display =
                    plate.includes(value) || owner.includes(value)
                        ? ''
                        : 'none';
            });
        });
    }


    /* =========================================================
       LIVE PARKING TIMER
    ========================================================= */

    function formatParkingTime(totalSeconds) {

        totalSeconds = Math.max(
            0,
            Math.floor(totalSeconds)
        );

        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return (
            hours + 'h ' +
            minutes + 'm ' +
            seconds + 's'
        );
    }


    const timers = document.querySelectorAll(
        '.timer[data-elapsed]'
    );

    timers.forEach(function (timer) {

        const initialElapsed = Math.max(
            0,
            parseInt(timer.getAttribute('data-elapsed'), 10) || 0
        );

        const browserStart = performance.now();

        function updateTimer() {

            const browserElapsed = Math.floor(
                (performance.now() - browserStart) / 1000
            );

            const totalElapsed =
                initialElapsed + browserElapsed;

            timer.textContent =
                formatParkingTime(totalElapsed);
        }

        updateTimer();

        setInterval(updateTimer, 250);
    });

})();
</script>

</body>
</html>