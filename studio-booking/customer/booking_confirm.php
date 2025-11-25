<?php
require_once '../includes/header.php';
requireRole(['customer']);

// Ambil booking_id dari URL
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    header("Location: booking.php");
    exit();
}

// Ambil detail booking
$sql = "SELECT b.*, s.name as studio_name, s.price_per_hour, u.full_name, u.email 
        FROM bookings b 
        JOIN studios s ON b.studio_id = s.id 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = '$booking_id' AND b.user_id = '{$_SESSION['user_id']}'";
        
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: booking.php");
    exit();
}

$booking = mysqli_fetch_assoc($result);

// Format data untuk WhatsApp
$whatsapp_number = "6285707725170";
$studio_name = $booking['studio_name'];
$booking_date = date('d/m/Y', strtotime($booking['booking_date']));
$start_time = date('H:i', strtotime($booking['start_time']));
$end_time = date('H:i', strtotime($booking['end_time']));
$duration = $booking['total_hours'];
$total_price = formatRupiah($booking['total_price']);
$customer_name = $booking['full_name'];

// Buat pesan template untuk WA
$wa_message = "Halo, saya ingin konfirmasi booking studio:\n\n";
$wa_message .= "*No. Booking:* #B" . str_pad($booking_id, 4, '0', STR_PAD_LEFT) . "\n";
$wa_message .= "*Nama:* " . $customer_name . "\n";
$wa_message .= "*Studio:* " . $studio_name . "\n";
$wa_message .= "*Tanggal:* " . $booking_date . "\n";
$wa_message .= "*Waktu:* " . $start_time . " - " . $end_time . " (" . $duration . " jam)\n";
$wa_message .= "*Total:* " . $total_price . "\n\n";
$wa_message .= "Silakan konfirmasi ketersediaan dan cara pembayaran.\nTerima kasih.";

// Encode pesan untuk URL
$encoded_message = urlencode($wa_message);
$wa_url = "https://wa.me/{$whatsapp_number}?text={$encoded_message}";
?>

<div class="container">
    <div class="booking-container">
        <div class="confirmation-card" style="background: var(--surface); padding: 2rem; border-radius: 8px; box-shadow: var(--shadow);">
            <div class="confirmation-header" style="text-align: center; margin-bottom: 2rem;">
                <div class="alert alert-success" style="font-size: 1.3rem; font-weight: 600; padding: 1rem 1.5rem;">
                    <span style="font-size: 1.5rem; margin-right: 0.5rem;"></span> 
                    Booking Berhasil!
                </div>
                <p style="color: var(--text-secondary); margin-top: 1rem;">Detail booking Anda telah disimpan. Silakan konfirmasi via WhatsApp untuk proses selanjutnya.</p>
            </div>

            <div class="booking-details">
                <h3 style="color: var(--primary-dark); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--primary);">Detail Booking</h3>
                
                <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="detail-item">
                        <div class="detail-label" style="font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem;">No. Booking</div>
                        <div class="detail-value" style="color: var(--primary-dark); font-weight: 600; font-size: 1.1rem;">#B<?php echo str_pad($booking_id, 4, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label" style="font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem;">Studio</div>
                        <div class="detail-value"><?php echo htmlspecialchars($studio_name); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label" style="font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem;">Tanggal</div>
                        <div class="detail-value"><?php echo $booking_date; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label" style="font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem;">Waktu</div>
                        <div class="detail-value"><?php echo $start_time; ?> - <?php echo $end_time; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label" style="font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem;">Durasi</div>
                        <div class="detail-value"><?php echo $duration; ?> Jam</div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label" style="font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem;">Total Harga</div>
                        <div class="detail-value" style="color: var(--primary-dark); font-weight: 600; font-size: 1.2rem;"><?php echo $total_price; ?></div>
                    </div>
                </div>
            </div>

            <div class="action-section" style="margin-top: 2.5rem;">
                <h4 style="color: var(--primary-dark); margin-bottom: 1rem; text-align: center;">Langkah Selanjutnya</h4>
                
                <div class="action-buttons" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo $wa_url; ?>" 
                       target="_blank" 
                       class="btn" 
                       style="background-color: #a5d6a7; color: white; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; transition: background-color 0.3s;">
                        <span style="font-size: 1rem;">Konfirmasi via WhatsApp</span> 
                    </a>
                    
                    <a href="booking.php" 
                       class="btn btn-primary" 
                       style="background-color: #a5d6a7; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">Booking Lagi</span> 
                    </a>
                    
                    <a href="../customer/index.php" 
                       class="btn btn-secondary" 
                       style="background-color: #a5d6a7; color: white; padding: 0.75rem 1.5rem; border-radius: 5px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1rem;">Ke Dashboard</span>
                    </a>
                </div>
            </div>

            <div class="info-note" style="margin-top: 2rem; padding: 1rem 1.5rem; background-color: #e8f5e9; border-radius: 4px; border-left: 4px solid var(--primary);">
                <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5;">
                    <strong>💡 Informasi Penting:</strong> Klik tombol <strong>"Konfirmasi via WhatsApp"</strong> untuk mengirim detail booking ke admin. 
                    Admin akan membalas untuk konfirmasi ketersediaan final dan mengirimkan instruksi pembayaran.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
/* Style tambahan untuk match dengan CSS existing */
.confirmation-card {
    max-width: 800px;
    margin: 0 auto;
}

.detail-grid {
    background: rgba(165, 214, 167, 0.1);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--border);
}

.btn {
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-family: "Poppins", sans-serif;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* WhatsApp button hover effect */
.btn[style*="background-color: #a5d6a7"]:hover {
    background-color: #75a478 !important;
}

.action-buttons .btn {
    min-width: 180px;
    justify-content: center;
}

@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .confirmation-card {
        padding: 1.5rem;
        margin: 1rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>