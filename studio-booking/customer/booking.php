<?php
require_once '../includes/header.php';
requireRole(['customer']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Proses booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studio_id = mysqli_real_escape_string($conn, $_POST['studio_id']);
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    
    // Validasi tanggal
    if (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $error = "Tanggal booking tidak boleh di masa lalu.";
    } else {
        // Validasi durasi
        if ($duration < 1 || $duration > 5) {
            $error = "Durasi booking harus antara 1-5 jam.";
        } else {
            // Hitung waktu selesai
            $start_timestamp = strtotime($start_time);
            $end_timestamp = $start_timestamp + ($duration * 3600);
            $end_time = date('H:i:s', $end_timestamp);
            
            // Validasi waktu selesai tidak melebihi jam 22:00
            if (date('H:i', $end_timestamp) > '22:00') {
                $error = "Waktu booking tidak boleh melebihi jam 22:00.";
            } else {
                // Ambil harga studio
                $sql = "SELECT price_per_hour FROM studios WHERE id = '$studio_id'";
                $result = mysqli_query($conn, $sql);
                
                if ($result && mysqli_num_rows($result) > 0) {
                    $studio = mysqli_fetch_assoc($result);
                    $total_price = $studio['price_per_hour'] * $duration;
                    
                    // Cek apakah studio available pada waktu tersebut
                    $sql = "SELECT id FROM bookings 
                            WHERE studio_id = '$studio_id' 
                            AND booking_date = '$booking_date'
                            AND (
                                (start_time <= '$start_time' AND end_time > '$start_time') OR
                                (start_time < '$end_time' AND end_time >= '$end_time') OR
                                (start_time >= '$start_time' AND end_time <= '$end_time')
                            )
                            AND status != 'cancelled'";
                    
                    $result = mysqli_query($conn, $sql);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        $error = "Studio tidak tersedia pada waktu yang dipilih.";
                    } else {
                        // Insert booking
                        $sql = "INSERT INTO bookings (user_id, studio_id, booking_date, start_time, end_time, total_hours, total_price) 
                                VALUES ('$user_id', '$studio_id', '$booking_date', '$start_time', '$end_time', '$duration', '$total_price')";
                        
                        if (mysqli_query($conn, $sql)) {
                            $success = "Booking berhasil dibuat. Silakan lakukan pembayaran.";
                        } else {
                            $error = "Error: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $error = "Studio tidak ditemukan.";
                }
            }
        }
    }
}

// Ambil data studio
$studio_id = isset($_GET['studio_id']) ? mysqli_real_escape_string($conn, $_GET['studio_id']) : 0;
$studio = null;

if ($studio_id) {
    $sql = "SELECT * FROM studios WHERE id = '$studio_id' AND status = 'available'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $studio = mysqli_fetch_assoc($result);
    }
}

// Ambil semua studio available
$sql = "SELECT * FROM studios WHERE status = 'available' ORDER BY name";
$studios = mysqli_query($conn, $sql);
?>

<div class="container">
    <div class="booking-container">
        <!-- Notifikasi dipindahkan ke sini - di dalam container utama -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="booking-form">
            <h2>Booking Studio</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="studio_id">Pilih Studio</label>
                    <select id="studio_id" name="studio_id" required onchange="updateStudioPrice()">
                        <option value="">-- Pilih Studio --</option>
                        <?php 
                        if ($studios && mysqli_num_rows($studios) > 0) {
                            // Reset pointer result set
                            mysqli_data_seek($studios, 0);
                            while($s = mysqli_fetch_assoc($studios)): 
                        ?>
                        <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['price_per_hour']; ?>" <?php echo ($studio_id == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?> - <?php echo formatRupiah($s['price_per_hour']); ?>/jam
                        </option>
                        <?php 
                            endwhile;
                        } else {
                            echo '<option value="">Tidak ada studio tersedia</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="booking_date">Tanggal Booking</label>
                    <input type="date" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required onchange="updateTimeConstraints()">
                </div>
                
                <div class="form-group">
                    <label for="start_time">Waktu Mulai</label>
                    <input type="time" id="start_time" name="start_time" min="08:00" max="22:00" required onchange="calculateEndTime()">
                </div>
                
                <div class="form-group">
                    <label for="duration">Durasi Booking</label>
                    <select id="duration" name="duration" required onchange="calculateEndTime()">
                        <option value="">-- Pilih Durasi --</option>
                        <option value="1">1 Jam</option>
                        <option value="2">2 Jam</option>
                        <option value="3">3 Jam</option>
                        <option value="4">4 Jam</option>
                        <option value="5">5 Jam</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Waktu Selesai: <span id="end_time_display" class="time-display">--:--</span></label>
                    <input type="hidden" id="end_time" name="end_time">
                </div>
                
                <div class="price-display">
                    <div class="label">Total Harga:</div>
                    <div class="value" id="total_price">Rp 0</div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Booking Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* CSS tambahan untuk memperbaiki layout */
.booking-container {
    width: 100%;
}

.alert {
    margin-bottom: 2rem !important;
}

.time-display {
    font-weight: bold;
    color: var(--primary-dark);
    padding: 4px 8px;
    background-color: #f5f5f5;
    border-radius: 4px;
}

/* Memastikan notifikasi muncul di atas dengan benar */
.booking-container .alert:first-child {
    margin-top: 0;
}
</style>

<script>
function updateStudioPrice() {
    calculateTotal();
}

function calculateEndTime() {
    const startTime = document.getElementById('start_time');
    const durationSelect = document.getElementById('duration');
    const endTimeDisplay = document.getElementById('end_time_display');
    const endTimeInput = document.getElementById('end_time');
    
    if (startTime.value && durationSelect.value) {
        const start = new Date('2000-01-01T' + startTime.value);
        const duration = parseInt(durationSelect.value);
        const end = new Date(start.getTime() + (duration * 60 * 60 * 1000));
        
        // Format waktu selesai
        const endHours = end.getHours().toString().padStart(2, '0');
        const endMinutes = end.getMinutes().toString().padStart(2, '0');
        const endTimeString = endHours + ':' + endMinutes;
        
        endTimeDisplay.textContent = endTimeString;
        endTimeInput.value = endTimeString + ':00';
        
        // Validasi tidak melebihi jam 22:00
        if (endTimeString > '22:00') {
            endTimeDisplay.style.color = 'red';
            endTimeDisplay.textContent = endTimeString + ' (Melebihi batas waktu)';
        } else {
            endTimeDisplay.style.color = '';
            endTimeDisplay.textContent = endTimeString;
        }
        
        calculateTotal();
    }
}

function calculateTotal() {
    const studioSelect = document.getElementById('studio_id');
    const durationSelect = document.getElementById('duration');
    const totalPriceSpan = document.getElementById('total_price');
    
    if (studioSelect.value && durationSelect.value) {
        const pricePerHour = parseFloat(studioSelect.options[studioSelect.selectedIndex].getAttribute('data-price'));
        const duration = parseInt(durationSelect.value);
        
        if (duration > 0) {
            totalPriceSpan.textContent = 'Rp ' + (pricePerHour * duration).toLocaleString('id-ID');
        } else {
            totalPriceSpan.textContent = 'Rp 0';
        }
    }
}

function updateTimeConstraints() {
    const bookingDate = document.getElementById('booking_date');
    const startTime = document.getElementById('start_time');
    const today = new Date().toISOString().split('T')[0];
    const selectedDate = bookingDate.value;
    
    if (selectedDate === today) {
        const now = new Date();
        const currentHour = now.getHours().toString().padStart(2, '0');
        const currentMinute = now.getMinutes().toString().padStart(2, '0');
        startTime.min = currentHour + ':' + currentMinute;
    } else {
        startTime.min = '08:00';
    }
    
    // Reset waktu mulai jika waktu yang dipilih kurang dari min
    if (startTime.value && startTime.value < startTime.min) {
        startTime.value = startTime.min;
    }
    
    calculateEndTime();
}

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    updateTimeConstraints();
});
</script>

<?php include '../includes/footer.php'; ?>