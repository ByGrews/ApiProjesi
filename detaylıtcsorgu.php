<?php

// Veritabanı bağlantısı
$db = new PDO("mysql:host=localhost;dbname=log", "root", "");

// Tablo adı
$tableName = 'requests';

// Sorgu limiti
$limit = 1000;

// Kullanıcının IP adresi
$user_ip = $_SERVER['REMOTE_ADDR'];

// Bugünün tarihi (Y-m-d formatında)
$today = date('Y-m-d');

// Tablo var mı kontrol et, yoksa oluştur
$query = $db->query("SHOW TABLES LIKE '{$tableName}'");
if ($query->rowCount() == 0) {
    $db->exec("
        CREATE TABLE {$tableName} (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            count INT(11) NOT NULL DEFAULT 0
        )
    ");
}

// Kullanıcının bugünkü istek sayısını kontrol et veya yeni kayıt ekle
$query = $db->prepare("SELECT count FROM {$tableName} WHERE ip = ? AND date = ?");
$query->execute([$user_ip, $today]);
$result = $query->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $count = $result['count'] + 1;
    $query = $db->prepare("UPDATE {$tableName} SET count = ? WHERE ip = ? AND date = ?");
    $query->execute([$count, $user_ip, $today]);
} else {
    $count = 1;
    $query = $db->prepare("INSERT INTO {$tableName} (ip, date, count) VALUES (?, ?, ?)");
    $query->execute([$user_ip, $today, $count]);
}

// Kullanıcının bugünkü istek sayısını kontrol et
$query = $db->prepare("SELECT count FROM {$tableName} WHERE ip = ? AND date = ?");
$query->execute([$user_ip, $today]);
$result = $query->fetch(PDO::FETCH_ASSOC);

if ($result['count'] >= $limit) {
    // Kullanıcı limiti aştı, hata mesajı göster veya 429 hata kodu döndür
    header("HTTP/1.1 429 Too Many Requests");
    echo "Günlük limit aşıldı.";
    exit();
}

error_reporting(0);
$tc = $_GET["tc"];
$connect = new mysqli("localhost", "root", "", "116m");
$sql = "SELECT * FROM gsm WHERE TC='$tc'";
$result = mysqli_query($connect, $sql) or die("Error in Selecting " . mysqli_error($connect));

$flash = array();
while ($hm = mysqli_fetch_assoc($result)) {
    $tc = $hm["TC"];
    $gsm = $hm["GSM"];
}

$connect2 = new mysqli("localhost", "root", "", "101m");
$sql2 = "SELECT * FROM 101m WHERE TC='$tc'";
$result2 = mysqli_query($connect2, $sql2) or die("Error in Selecting " . mysqli_error($connect2));

$adres_url = "http://localhost/vrg.php?tc=" . $tc;
$adres_json = file_get_contents($adres_url);
$adres_data = json_decode($adres_json, true);

if ($adres_data["success"] == true) {
    $adres = $adres_data["adres"];
    $vergino = $adres_data["vergino"];
    $vergidadi = $adres_data["vergidadi"];
    $vergidkodu = $adres_data["vergidkodu"];
} else {
    echo "API'den adres verisi alınamadı.";
}

$operator_url = "http://localhost/operatör.php?phone=" . $gsm;
$operator_json = file_get_contents($operator_url);
$operator_data = json_decode($operator_json, true);

if ($operator_data["success"] == true) {
    $operator = $operator_data["operator"];
} else {
    echo "API'den operatör bilgisi alınamadı.";
}

$flash2 = array();
while ($hm2 = mysqli_fetch_assoc($result2)) {
    $ad = $hm2["ADI"];
    $soyad = $hm2["SOYADI"];
    $dogumtarıhı = $hm2["DOGUMTARIHI"];
    $annead = $hm2["ANNEADI"];
    $annetc = $hm2["ANNETC"];
    $babaad = $hm2["BABAADI"];
    $babatc = $hm2["BABATC"];
    $il = $hm2["NUFUSIL"];
    $ilce = $hm2["NUFUSILCE"];
}

$dog = "$dogumtarıhı";
$bugun = date("Y-m-d");
$diff = date_diff(date_create($dog), date_create($bugun));

$data = array(
    "Kimlik Bilgileri" => array(
        "Adı" => $ad,
        "Soyadı" => $soyad,
        "DogumTarihi" => $dogumtarıhı,
        "Yaş" => $diff->format('%Y YIL %m AY %d GÜN'),
        "AnneAd" => $annead,
        "AnneTc" => $annetc,
        "BabaAd" => $babaad,
        "BabaTc" => $babatc,
        "İl" => $il,
        "İlce" => $ilce
    ),
    "Telefon Bilgileri" => array(
        "Gsm" => $gsm,
        "Operatör" => $operator
    ),
    "Adres Bilgileri" => array(
        "Adres" => $adres,
        "VergiNo" => $vergino,
        "VergiDadi" => $vergidadi,
        "VergiDkodu" => $vergidkodu
    ),
);

$response = array(
    "data" => $data
);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Maker By Grews</title>
    <style>
        body {
            background-color: #33ebff;
            color: black;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        table {
            background-color: white;
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .copy-button {
            background-color: #003399;
            color: white;
            font-weight: bold;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            position: fixed;
            top: 20px;
            right: 20px;
            font-size: 14px;
        }

        .telegram-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            animation: shake 0.5s infinite;
            cursor: pointer;
        }

        .telegram-border {
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border: 2px solid #003399;
            border-radius: 10px;
            animation: pulse 2s infinite;
        }

        .telegram-text {
            color: #003399;
            font-size: 18px;
            font-weight: bold;
            padding: 10px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes shake {
            0% {
                transform: translateY(0);
            }
            25% {
                transform: translateY(-5px);
            }
            50% {
                transform: translateY(5px);
            }
            75% {
                transform: translateY(-5px);
            }
            100% {
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <center>
        <h1>Sorgu Sonuçları</h1>
    </center>
    <table>
        <?php if ($response && isset($response['data'])): ?>
        <?php foreach ($response['data'] as $category => $values) : ?>
        <tr>
            <th><?php echo $category; ?></th>
            <td>
                <table>
                    <?php foreach ($values as $label => $value) : ?>
                    <tr>
                        <th><?php echo $label; ?></th>
                        <td><?php echo $value; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr>
            <td colspan="2">Veriler bulunamadı.</td>
        </tr>
        <?php endif; ?>
    </table>
    <div class="telegram-container" onclick="redirectToTelegram()">
        <div class="telegram-border"></div>
        <a class="telegram-text" href="https://t.me/Anonim_321321" target="_blank" rel="noopener noreferrer">Telegram</a>
    </div>
    <button class="copy-button" onclick="copyResults()">Sonuçları Kopyala</button>

    <?php
    // Toplam limit
    $limit = 1000;
    // Kalan sorgu limiti
    $remaining = $limit - $count;
    ?>

    <div>
        <center>
            <h2>Toplam Soru Atılma Limit: <?php echo $limit; ?></h2>
        </center>
        <center>
            <h2>Kalan Sorgu Atılma Limiti: <?php echo $remaining; ?></h2>
        </center>
    </div>

    <script>
        function copyResults() {
            var resultTable = document.querySelector('table');
            var resultText = '';

            // Gezinti
            Array.from(resultTable.rows).forEach(function(row) {
                var labelCell = row.cells[0];
                var valueCell = row.cells[1];

                var label = labelCell.textContent.trim();
                var value = valueCell.textContent.trim();

                resultText += label + ': ' + value + '\n';
            });

            // Kopyalama
            var tempElement = document.createElement('textarea');
            tempElement.value = resultText;
            document.body.appendChild(tempElement);
            tempElement.select();
            document.execCommand('copy');
            document.body.removeChild(tempElement);

            // Bildirim
            alert('Sonuçlar kopyalandı! Maker By Grews </>');
        }

        function redirectToTelegram() {
            window.open('https://t.me/Anonim_321321', '_blank');
        }
    </script>
</body>

</html>
