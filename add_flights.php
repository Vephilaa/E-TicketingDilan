<?php
// Script untuk menambahkan jadwal penerbangan dummy dengan tanggal realistis
require_once 'config/config.php';

try {       
    $database = new Database();
    $db = $database->getConnection();

    // Hapus semua flight yang ada
    $db->exec("DELETE FROM flights");
    echo "Menghapus semua penerbangan lama...<br>";

    // Tambahkan jadwal baru dengan tanggal realistis
    $flights = [
        // Hari ini
        ['GA101', 1, 1, 2, date('Y-m-d 08:00:00'), date('Y-m-d 10:30:00'), 1500000, 150, 180],
        ['JT201', 2, 1, 3, date('Y-m-d 09:00:00'), date('Y-m-d 10:45:00'), 850000, 160, 200],
        ['QG301', 3, 1, 4, date('Y-m-d 07:00:00'), date('Y-m-d 09:15:00'), 1800000, 80, 120],
        ['ID401', 4, 1, 5, date('Y-m-d 10:00:00'), date('Y-m-d 13:30:00'), 2100000, 100, 150],
        ['SJ501', 5, 1, 9, date('Y-m-d 11:00:00'), date('Y-m-d 12:30:00'), 750000, 130, 160],

        // Besok
        ['GA102', 1, 1, 2, date('Y-m-d', strtotime('+1 day')) . ' 08:00:00', date('Y-m-d', strtotime('+1 day')) . ' 10:30:00', 1500000, 120, 180],
        ['GA103', 1, 1, 3, date('Y-m-d', strtotime('+1 day')) . ' 14:00:00', date('Y-m-d', strtotime('+1 day')) . ' 15:45:00', 950000, 140, 180],
        ['JT202', 2, 1, 2, date('Y-m-d', strtotime('+1 day')) . ' 09:00:00', date('Y-m-d', strtotime('+1 day')) . ' 11:30:00', 1200000, 150, 200],
        ['JT203', 2, 1, 4, date('Y-m-d', strtotime('+1 day')) . ' 06:30:00', date('Y-m-d', strtotime('+1 day')) . ' 08:45:00', 1500000, 110, 160],
        ['QG302', 3, 2, 1, date('Y-m-d', strtotime('+1 day')) . ' 11:00:00', date('Y-m-d', strtotime('+1 day')) . ' 13:30:00', 1400000, 90, 120],
        ['ID402', 4, 1, 6, date('Y-m-d', strtotime('+1 day')) . ' 09:00:00', date('Y-m-d', strtotime('+1 day')) . ' 10:30:00', 1300000, 85, 120],
        ['SJ502', 5, 3, 1, date('Y-m-d', strtotime('+1 day')) . ' 14:00:00', date('Y-m-d', strtotime('+1 day')) . ' 16:00:00', 950000, 100, 140],

        // 2 hari lagi
        ['GA104', 1, 1, 2, date('Y-m-d', strtotime('+2 days')) . ' 14:00:00', date('Y-m-d', strtotime('+2 days')) . ' 16:30:00', 1500000, 130, 180],
        ['GA105', 1, 1, 5, date('Y-m-d', strtotime('+2 days')) . ' 08:00:00', date('Y-m-d', strtotime('+2 days')) . ' 11:30:00', 2100000, 90, 150],
        ['JT204', 2, 1, 3, date('Y-m-d', strtotime('+2 days')) . ' 08:00:00', date('Y-m-d', strtotime('+2 days')) . ' 09:45:00', 750000, 170, 220],
        ['JT205', 2, 1, 9, date('Y-m-d', strtotime('+2 days')) . ' 10:00:00', date('Y-m-d', strtotime('+2 days')) . ' 11:30:00', 600000, 160, 200],
        ['QG303', 3, 1, 8, date('Y-m-d', strtotime('+2 days')) . ' 10:00:00', date('Y-m-d', strtotime('+2 days')) . ' 11:00:00', 600000, 110, 140],
        ['ID403', 4, 1, 7, date('Y-m-d', strtotime('+2 days')) . ' 08:00:00', date('Y-m-d', strtotime('+2 days')) . ' 09:45:00', 1100000, 95, 140],
        ['SJ503', 5, 1, 10, date('Y-m-d', strtotime('+2 days')) . ' 09:30:00', date('Y-m-d', strtotime('+2 days')) . ' 11:00:00', 550000, 140, 180],

        // 3 hari lagi
        ['GA106', 1, 1, 4, date('Y-m-d', strtotime('+3 days')) . ' 07:00:00', date('Y-m-d', strtotime('+3 days')) . ' 09:15:00', 1800000, 85, 120],
        ['GA107', 1, 1, 11, date('Y-m-d', strtotime('+3 days')) . ' 06:00:00', date('Y-m-d', strtotime('+3 days')) . ' 07:45:00', 1200000, 90, 120],
        ['JT206', 2, 1, 2, date('Y-m-d', strtotime('+3 days')) . ' 16:00:00', date('Y-m-d', strtotime('+3 days')) . ' 18:30:00', 1200000, 130, 200],
        ['JT207', 2, 1, 11, date('Y-m-d', strtotime('+3 days')) . ' 08:30:00', date('Y-m-d', strtotime('+3 days')) . ' 10:15:00', 950000, 120, 150],
        ['QG304', 3, 2, 3, date('Y-m-d', strtotime('+3 days')) . ' 08:30:00', date('Y-m-d', strtotime('+3 days')) . ' 10:15:00', 1100000, 100, 120],
        ['ID404', 4, 1, 13, date('Y-m-d', strtotime('+3 days')) . ' 10:00:00', date('Y-m-d', strtotime('+3 days')) . ' 12:30:00', 1700000, 70, 100],
        ['SJ504', 5, 3, 9, date('Y-m-d', strtotime('+3 days')) . ' 11:00:00', date('Y-m-d', strtotime('+3 days')) . ' 12:30:00', 700000, 110, 150],

        // 4 hari lagi
        ['GA108', 1, 1, 12, date('Y-m-d', strtotime('+4 days')) . ' 09:30:00', date('Y-m-d', strtotime('+4 days')) . ' 11:15:00', 1100000, 100, 140],
        ['GA109', 1, 1, 14, date('Y-m-d', strtotime('+4 days')) . ' 08:00:00', date('Y-m-d', strtotime('+4 days')) . ' 12:00:00', 2500000, 60, 80],
        ['JT208', 2, 1, 12, date('Y-m-d', strtotime('+4 days')) . ' 09:00:00', date('Y-m-d', strtotime('+4 days')) . ' 10:45:00', 900000, 130, 180],
        ['JT209', 2, 1, 5, date('Y-m-d', strtotime('+4 days')) . ' 11:00:00', date('Y-m-d', strtotime('+4 days')) . ' 14:30:00', 1800000, 120, 180],
        ['QG305', 3, 2, 9, date('Y-m-d', strtotime('+4 days')) . ' 12:00:00', date('Y-m-d', strtotime('+4 days')) . ' 13:30:00', 850000, 120, 150],
        ['ID405', 4, 4, 1, date('Y-m-d', strtotime('+4 days')) . ' 17:00:00', date('Y-m-d', strtotime('+4 days')) . ' 19:15:00', 2150000, 75, 100],
        ['SJ505', 5, 1, 4, date('Y-m-d', strtotime('+4 days')) . ' 11:00:00', date('Y-m-d', strtotime('+4 days')) . ' 13:30:00', 1550000, 65, 100],

        // 5 hari lagi
        ['GA110', 1, 1, 15, date('Y-m-d', strtotime('+5 days')) . ' 07:30:00', date('Y-m-d', strtotime('+5 days')) . ' 09:45:00', 2000000, 80, 120],
        ['JT210', 2, 1, 10, date('Y-m-d', strtotime('+5 days')) . ' 07:30:00', date('Y-m-d', strtotime('+5 days')) . ' 09:00:00', 550000, 150, 200],
        ['QG306', 3, 1, 2, date('Y-m-d', strtotime('+5 days')) . ' 08:00:00', date('Y-m-d', strtotime('+5 days')) . ' 10:30:00', 1350000, 110, 120],
        ['ID406', 4, 6, 1, date('Y-m-d', strtotime('+5 days')) . ' 11:00:00', date('Y-m-d', strtotime('+5 days')) . ' 12:30:00', 1250000, 85, 120],
        ['SJ506', 5, 1, 2, date('Y-m-d', strtotime('+5 days')) . ' 13:30:00', date('Y-m-d', strtotime('+5 days')) . ' 16:00:00', 1200000, 90, 120],

        // Return flights
        ['GA201', 1, 2, 1, date('Y-m-d') . ' 11:00:00', date('Y-m-d') . ' 13:30:00', 1450000, 140, 180],
        ['GA202', 1, 3, 1, date('Y-m-d') . ' 11:15:00', date('Y-m-d') . ' 13:00:00', 950000, 130, 180],
        ['JT801', 2, 2, 1, date('Y-m-d') . ' 12:00:00', date('Y-m-d') . ' 14:30:00', 1150000, 150, 200],
        ['JT802', 2, 3, 1, date('Y-m-d') . ' 10:15:00', date('Y-m-d') . ' 12:00:00', 750000, 170, 220],
        ['QG901', 3, 8, 1, date('Y-m-d', strtotime('+1 day')) . ' 11:30:00', date('Y-m-d', strtotime('+1 day')) . ' 12:30:00', 650000, 110, 140],
        ['SJ601', 5, 9, 1, date('Y-m-d', strtotime('+2 days')) . ' 13:00:00', date('Y-m-d', strtotime('+2 days')) . ' 14:30:00', 700000, 120, 150],
    ];

    // Insert flights
    $query = "INSERT INTO flights (flight_number, airline_id, departure_airport_id, arrival_airport_id, departure_time, arrival_time, price, available_seats, total_seats) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);

    $inserted = 0;
    foreach ($flights as $flight) {
        $stmt->execute($flight);
        $inserted++;
    }

    echo "<h3>Berhasil menambahkan {$inserted} jadwal penerbangan!</h3>";
    echo "<p>Jadwal mencakup penerbangan dari hari ini hingga 5 hari ke depan</p>";
    echo "<p><a href='user/flights.php'>Lihat Jadwal Penerbangan</a></p>";

} catch(PDOException $exception) {
    echo "Error: " . $exception->getMessage();
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}

h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

p {
    margin: 10px 0;
    line-height: 1.6;
}

a {
    display: inline-block;
    background: #3498db;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
}

a:hover {
    background: #2980b9;
}
</style>
