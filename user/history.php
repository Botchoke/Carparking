<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

if($search) {
    $history = mysqli_query($conn, "
        SELECT * FROM parking_sessions 
        WHERE user_id='$user_id' AND status='completed'
        AND (plate_number LIKE '%$search%' OR slot_number LIKE '%$search%')
        ORDER BY id DESC
    ");
} else {
    $history = mysqli_query($conn, "
        SELECT * FROM parking_sessions 
        WHERE user_id='$user_id' AND status='completed'
        ORDER BY id DESC
    ");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Parking History - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <a href="history.php" class="active"><i class="fas fa-history"></i> History</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-history"></i> My Parking History</h1>

        <div class="history-search" style="margin-bottom:20px;">
            <form method="GET" action="history.php" style="display:flex;background:rgba(10,15,35,0.8);border-radius:12px;border:1px solid rgba(79,124,172,0.2);padding:4px 16px;">
                <i class="fas fa-search" style="color:#4F7CAC;align-self:center;"></i>
                <input type="text" name="search" placeholder="Search by plate or slot..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;background:transparent;border:none;padding:12px 14px;color:#9cfaff;outline:none;">
                <button type="submit" style="background:linear-gradient(135deg,#4F7CAC,#2F5A86);border:none;color:white;padding:8px 20px;border-radius:8px;font-weight:600;cursor:pointer;">Search</button>
            </form>
        </div>

        <?php if(mysqli_num_rows($history) > 0): ?>
        <div class="history-table-wrapper">
            <table class="user-history-table">
                <thead>
                    <tr>
                        <th>Plate</th>
                        <th>Slot</th>
                        <th>Plan</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($h = mysqli_fetch_assoc($history)): 
                        $receiptId   = 'RCP-' . str_pad($h['id'], 4, '0', STR_PAD_LEFT);
                        $durationMin = 0;
                        if(!empty($h['time_in']) && !empty($h['time_out'])){
                            $durationMin = round((strtotime($h['time_out']) - strtotime($h['time_in'])) / 60);
                        }
                        $durationText = $durationMin >= 60
                            ? floor($durationMin / 60) . 'h ' . ($durationMin % 60) . 'm'
                            : $durationMin . 'm';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($h['plate_number']); ?></strong></td>
                        <td><?php echo $h['slot_number']; ?></td>
                        <td><?php echo htmlspecialchars($h['plan_name']); ?></td>
                        <td><?php echo date('M d, H:i', strtotime($h['time_in'])); ?></td>
                        <td><?php echo date('M d, H:i', strtotime($h['time_out'])); ?></td>
                        <td class="amount">
                            ₱<?php echo number_format($h['total_fee'], 2); ?>
                            <?php if(!empty($h['overtime_hours']) && $h['overtime_hours'] > 0): ?>
                                <small class="overtime-tag">+<?php echo $h['overtime_hours']; ?>h overtime</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($h['payment_method']); ?></td>
                        <td><span class="badge-paid">Paid</span></td>
                        <td>
                            <button type="button" class="receipt-btn" onclick="openReceipt(this)"
                                data-receipt="<?php echo $receiptId; ?>"
                                data-date="<?php echo date('Y-m-d', strtotime($h['time_out'])); ?>"
                                data-plate="<?php echo htmlspecialchars($h['plate_number']); ?>"
                                data-slot="<?php echo htmlspecialchars($h['slot_number']); ?>"
                                data-plan="<?php echo htmlspecialchars($h['plan_name']); ?>"
                                data-time-in="<?php echo date('h:i A', strtotime($h['time_in'])); ?>"
                                data-time-out="<?php echo date('h:i A', strtotime($h['time_out'])); ?>"
                                data-duration="<?php echo $durationText; ?>"
                                data-method="<?php echo htmlspecialchars($h['payment_method']); ?>"
                                data-total="<?php echo number_format($h['total_fee'], 2); ?>">
                                <i class="fas fa-receipt"></i> Receipt
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-parking"></i>
            <p>No parking history found.</p>
            <a href="index.php">Start Parking</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- RECEIPT MODAL -->
<div class="receipt-modal" id="receiptModal">
    <div class="receipt-overlay" onclick="closeReceipt()"></div>
    <div class="receipt-content">
        <button class="receipt-close" onclick="closeReceipt()"><i class="fas fa-times"></i></button>
        <div class="receipt" id="receiptContent"></div>
        <div class="receipt-actions">
            <button class="print-btn" onclick="printReceipt()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>
</div>

<div class="notification-toast" id="successToast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Success!</span>
</div>

<script>
function openReceipt(btn) {
    var receiptNo  = btn.getAttribute('data-receipt');
    var date       = btn.getAttribute('data-date');
    var plate      = btn.getAttribute('data-plate');
    var slot       = btn.getAttribute('data-slot');
    var plan       = btn.getAttribute('data-plan');
    var timeIn     = btn.getAttribute('data-time-in');
    var timeOut    = btn.getAttribute('data-time-out');
    var duration   = btn.getAttribute('data-duration');
    var method     = btn.getAttribute('data-method');
    var total      = btn.getAttribute('data-total');

    var html = ''
        + '<div class="receipt-header">'
        +   '<h1>PARKREV</h1>'
        +   '<div class="sub">Parking Management System</div>'
        +   '<div class="phone">Tel: (02) 8888-0000</div>'
        + '</div>'
        + '<div class="receipt-body">'
        +   '<div class="receipt-row"><span class="label">Receipt No:</span><span class="value">' + receiptNo + '</span></div>'
        +   '<div class="receipt-row"><span class="label">Date:</span><span class="value">' + date + '</span></div>'
        +   '<div class="receipt-divider"></div>'
        +   '<div class="receipt-section-title">SESSION DETAILS</div>'
        +   '<div class="receipt-row"><span class="label">Plate No.</span><span class="value">' + plate + '</span></div>'
        +   '<div class="receipt-row"><span class="label">Slot</span><span class="value">' + slot + '</span></div>'
        +   '<div class="receipt-row"><span class="label">Plan</span><span class="value">' + plan + '</span></div>'
        +   '<div class="receipt-row"><span class="label">Time In</span><span class="value">' + timeIn + '</span></div>'
        +   '<div class="receipt-row"><span class="label">Time Out</span><span class="value">' + timeOut + '</span></div>'
        +   '<div class="receipt-row"><span class="label">Duration</span><span class="value">' + duration + '</span></div>'
        +   '<div class="receipt-divider"></div>'
        +   '<div class="receipt-section-title">PAYMENT</div>'
        +   '<div class="receipt-row"><span class="label">Method</span><span class="value">' + method + '</span></div>'
        +   '<div class="receipt-row"><span class="label">Status</span><span class="value" style="color:#86efac;">Paid</span></div>'
        +   '<div class="receipt-row"><span class="label">Verified By</span><span class="value">Admin</span></div>'
        +   '<div class="receipt-divider"></div>'
        +   '<div class="receipt-row total"><span class="label">TOTAL</span><span class="value">₱' + total + '</span></div>'
        + '</div>'
        + '<div class="receipt-footer">'
        +   '<div class="thankyou">Thank you for parking with us!</div>'
        +   '<div>Please drive safely.</div>'
        +   '<div class="ref">' + receiptNo.replace('RCP-', 'SESSION ') + '</div>'
        + '</div>';

    document.getElementById('receiptContent').innerHTML = html;
    document.getElementById('receiptModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeReceipt() {
    document.getElementById('receiptModal').classList.remove('active');
    document.body.style.overflow = '';
}

function printReceipt() {
    var content = document.getElementById('receiptContent').innerHTML;
    var win = window.open('', '_blank', 'width=400,height=600');

    win.document.write('<!DOCTYPE html><html><head><title>Receipt</title><style>'
        + 'body{font-family:Courier New,monospace;color:#000;background:#fff;padding:20px;font-size:12px;line-height:1.5;}'
        + '.receipt-header{text-align:center;padding-bottom:12px;border-bottom:2px dashed #000;margin-bottom:12px;}'
        + '.receipt-header h1{font-size:20px;font-weight:700;letter-spacing:3px;margin-bottom:4px;}'
        + '.receipt-header .sub{font-size:10px;color:#333;margin-bottom:3px;}'
        + '.receipt-header .phone{font-size:10px;color:#666;}'
        + '.receipt-row{display:flex;justify-content:space-between;padding:4px 0;font-size:11px;}'
        + '.receipt-row .label{color:#333;}'
        + '.receipt-row .value{color:#000;font-weight:600;}'
        + '.receipt-divider{border-bottom:1px dashed #999;margin:10px 0;}'
        + '.receipt-section-title{font-size:10px;font-weight:700;letter-spacing:1px;margin-bottom:6px;}'
        + '.receipt-row.total{font-size:16px;font-weight:700;border-top:2px solid #000;padding-top:10px;margin-top:8px;}'
        + '.receipt-footer{text-align:center;padding-top:12px;border-top:2px dashed #000;margin-top:12px;font-size:10px;color:#333;}'
        + '.receipt-footer .thankyou{font-weight:700;color:#000;margin-bottom:4px;}'
        + '.receipt-footer .ref{margin-top:8px;font-size:9px;color:#666;letter-spacing:1px;}'
        + '@media print{body{padding:10px;}@page{margin:8mm;}}'
        + '</style></head><body>' + content + '</body></html>');

    win.document.close();
    setTimeout(function(){ win.focus(); win.print(); }, 300);
}

document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeReceipt();
});
</script>

<script src="script.js"></script>
</body>
</html>