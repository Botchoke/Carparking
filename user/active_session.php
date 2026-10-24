
<?php
session_start();
date_default_timezone_set('Asia/Manila');

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'user'
) {
    header("Location: login.php");
    exit();
}

include "../db.php";

$user_id = (int)$_SESSION['user_id'];

// Request exit
if (isset($_POST['request_exit'])) {
    $sess_id = (int)($_POST['sess_id'] ?? 0);

    $stmt = mysqli_prepare($conn, "
        UPDATE parking_sessions
        SET exit_requested = 1,
            exit_requested_at = NOW()
        WHERE id = ?
          AND user_id = ?
          AND status = 'active'
    ");

    mysqli_stmt_bind_param($stmt, "ii", $sess_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: active_session.php?id=" . $sess_id . "&exit=requested");
    exit();
}

// Get session
$sess_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sess_id > 0) {
    $stmt = mysqli_prepare($conn, "
        SELECT *
        FROM parking_sessions
        WHERE id = ? AND user_id = ? AND status = 'active'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "ii", $sess_id, $user_id);
} else {
    $stmt = mysqli_prepare($conn, "
        SELECT *
        FROM parking_sessions
        WHERE user_id = ? AND status = 'active'
        ORDER BY id DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$session = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$session) {
    header("Location: index.php");
    exit();
}

// Calculate elapsed time using the MySQL server clock
$stmt = mysqli_prepare($conn, "
    SELECT
        TIMESTAMPDIFF(SECOND, time_in, NOW()) AS elapsed_seconds
    FROM parking_sessions
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "ii", $session['id'], $user_id);
mysqli_stmt_execute($stmt);
$elapsedResult = mysqli_stmt_get_result($stmt);
$elapsedRow = mysqli_fetch_assoc($elapsedResult);
mysqli_stmt_close($stmt);

$elapsed = max(0, (int)($elapsedRow['elapsed_seconds'] ?? 0));
$initialElapsed = $elapsed;

$start = strtotime($session['time_in']);
$planned = (int)$session['duration_hours'] * 3600;
$graceSeconds = 15 * 60;

$overtimeSeconds = max(0, $elapsed - $planned - $graceSeconds);
$overtimeHours = (int)ceil($overtimeSeconds / 3600);
$overtimeFee = $overtimeHours * 20;

$totalDue = (float)$session['total_fee'] + $overtimeFee;
$isOvertime = $overtimeHours > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Session - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="user-dashboard">
    <header class="user-header">
        <div class="user-brand">
            <h1><i class="fas fa-parking"></i> HYPERCAR <span>PARKING</span></h1>
        </div>

        <nav class="user-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="my_vehicles.php"><i class="fas fa-car"></i> My Vehicles</a>
            <a href="my_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="page-content">
        <h1 class="page-title">
            <i class="fas fa-clock"></i>
            Active Session: <?php echo htmlspecialchars($session['plate_number']); ?>
        </h1>

        <div style="text-align:center; margin-bottom:20px;">
            <a href="index.php" style="color:var(--cyan);text-decoration:none;font-size:13px;">
                <i class="fas fa-arrow-left"></i> Back to All Sessions
            </a>
        </div>

        <?php if (isset($_GET['exit']) && $_GET['exit'] === 'requested'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                Exit request sent! Please proceed to the exit gate.
            </div>
        <?php endif; ?>

        <div class="session-overview">
            <div class="session-timer-card <?php echo $isOvertime ? 'overtime' : ''; ?>">
                <div class="session-timer">0h 0m 0s</div>

                <div class="session-status" id="sessionStatus">
                    <?php if ($isOvertime): ?>
                        <span class="status-overtime">
                            <i class="fas fa-exclamation-triangle"></i> OVERDUE
                        </span>
                    <?php else: ?>
                        <span class="status-ok">
                            <i class="fas fa-check-circle"></i> ON TIME
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="session-details-grid">
                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-car"></i> Plate</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($session['plate_number']); ?>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-parking"></i> Slot</div>
                    <div class="detail-value">
                        #<?php echo htmlspecialchars($session['slot_number']); ?>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-list"></i> Plan</div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($session['plan_name']); ?>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-sign-in-alt"></i> Time In</div>
                    <div class="detail-value">
                        <?php echo $start !== false ? date('h:i A', $start) : '--'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="billing-card <?php echo $isOvertime ? 'billing-overtime' : ''; ?>">
            <h3><i class="fas fa-receipt"></i> Current Bill</h3>

            <div class="billing-row">
                <span>Base fee (<?php echo htmlspecialchars($session['plan_name']); ?>)</span>
                <span>₱<?php echo number_format((float)$session['total_fee'], 2); ?></span>
            </div>

            <?php if ($isOvertime): ?>
                <div class="billing-row overtime-row">
                    <span>
                        <i class="fas fa-exclamation-triangle"></i>
                        Overtime (<?php echo $overtimeHours; ?> hr × ₱20)
                    </span>
                    <span>+ ₱<?php echo number_format($overtimeFee, 2); ?></span>
                </div>
            <?php endif; ?>

            <div class="billing-row total-row">
                <span>Total Due</span>
                <span>₱<?php echo number_format($totalDue, 2); ?></span>
            </div>

            <div class="billing-row" style="margin-top:16px;border-top:1px solid var(--line);padding-top:16px;">
                <span>Payment Method</span>
                <span><?php echo htmlspecialchars($session['payment_method']); ?></span>
            </div>
        </div>

        <?php if (empty($session['exit_requested'])): ?>
            <div class="exit-action-card">
                <h3><i class="fas fa-door-open"></i> Ready to Leave?</h3>
                <p>
                    Click the button below and our staff will be notified.
                    Proceed to the exit gate to complete your payment.
                </p>

                <form method="POST" onsubmit="return confirm('Confirm you are leaving now?');">
                    <input type="hidden" name="sess_id" value="<?php echo (int)$session['id']; ?>">
                    <button type="submit" name="request_exit" class="btn-exit-request">
                        <i class="fas fa-sign-out-alt"></i> I'm Leaving Now
                    </button>
                </form>

                <small class="exit-note">
                    <i class="fas fa-info-circle"></i>
                    A staff member will verify your vehicle at the exit gate.
                </small>
            </div>
        <?php else: ?>
            <div class="exit-action-card exit-pending">
                <i class="fas fa-hourglass-half"></i>
                <h3>Exit Request Sent</h3>
                <p>Waiting for staff to process. Please proceed to the exit gate.</p>
                <div class="pending-time">
                    Requested:
                    <?php
                    echo !empty($session['exit_requested_at'])
                        ? date('h:i A', strtotime($session['exit_requested_at']))
                        : 'Pending';
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="notification-toast" id="successToast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Success!</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const timerEl = document.querySelector('.session-timer');
    const timerCard = document.querySelector('.session-timer-card');
    const statusEl = document.getElementById('sessionStatus');

    if (!timerEl) return;

    // Initial elapsed seconds are calculated by MySQL.
    const initialElapsed = <?php echo (int)$initialElapsed; ?>;
    const plannedSeconds = <?php echo (int)$planned; ?>;
    const graceSeconds = <?php echo (int)$graceSeconds; ?>;

    // Monotonic clock counts up without depending on the PC's date/time.
    const pageStart = performance.now();

    function updateTimer() {
        const elapsed = initialElapsed +
            Math.floor((performance.now() - pageStart) / 1000);

        const hours = Math.floor(elapsed / 3600);
        const minutes = Math.floor((elapsed % 3600) / 60);
        const seconds = elapsed % 60;

        timerEl.textContent = `${hours}h ${minutes}m ${seconds}s`;

        const overtime = elapsed > plannedSeconds + graceSeconds;

        if (timerCard) {
            timerCard.classList.toggle('overtime', overtime);
        }

        if (statusEl) {
            statusEl.innerHTML = overtime
                ? '<span class="status-overtime"><i class="fas fa-exclamation-triangle"></i> OVERDUE</span>'
                : '<span class="status-ok"><i class="fas fa-check-circle"></i> ON TIME</span>';
        }
    }

    updateTimer();
    setInterval(updateTimer, 1000);
});
</script>

<script src="script.js"></script>
</body>
</html>