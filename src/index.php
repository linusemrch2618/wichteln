<?php

session_start();

// Dateiname für die Speicherung der Paarungen
$dataFile = 'wichtel_data.json';
$message = '';
$msgType = ''; // 'error' oder 'success'
$resultName = '';

// --- LOGIK: SETUP (PAARUNGEN ERSTELLEN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup') {
    $namesInput = trim($_POST['participants']);
    
    if (empty($namesInput)) {
        $message = "Bitte gib mindestens 3 Namen ein.";
        $msgType = 'error';
    } else {
        // Namen am Zeilenumbruch trennen und bereinigen
        $names = array_filter(array_map('trim', explode("\n", $namesInput)));
        $names = array_unique($names); // Duplikate entfernen

        if (count($names) < 2) {
            $message = "Es werden mindestens 2 Personen zum Wichteln benötigt.";
            $msgType = 'error';
        } else {
            // Wichtel-Logik: Liste mischen und im Kreis zuordnen
            shuffle($names);
            $pairs = [];
            $count = count($names);

            for ($i = 0; $i < $count; $i++) {
                $giver = $names[$i];
                // Der Beschenkte ist der Nächste in der Liste (der Letzte schenkt dem Ersten)
                $receiver = $names[($i + 1) % $count];
                
                // Wir speichern Keys in Kleinbuchstaben für einfacheren Vergleich
                // Fallback, falls mb_strtolower nicht existiert
                $key = function_exists('mb_strtolower') ? mb_strtolower($giver) : strtolower($giver);
                $pairs[$key] = [
                    'giver_display' => $giver,
                    'receiver' => $receiver
                ];
            }

            // Speichern
            if (file_put_contents($dataFile, json_encode($pairs))) {
                $message = "Die Wichtel wurden erfolgreich ausgelost! Jetzt kann jeder seinen Namen eingeben.";
                $msgType = 'success';
            } else {
                $message = "Fehler beim Speichern. Bitte Schreibrechte im Ordner prüfen.";
                $msgType = 'error';
            }
        }
    }
}

// --- LOGIK: RESET (NEU STARTEN) ---
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    if (file_exists($dataFile)) {
        unlink($dataFile);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- LOGIK: ABFRAGE (WER MUSS WEN BESCHENKEN?) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lookup') {
    if (file_exists($dataFile)) {
        $pairs = json_decode(file_get_contents($dataFile), true);
        $myName = trim($_POST['my_name']);
        
        // Fallback, falls mb_strtolower nicht existiert
        $searchKey = function_exists('mb_strtolower') ? mb_strtolower($myName) : strtolower($myName);

        if (array_key_exists($searchKey, $pairs)) {
            $resultName = $pairs[$searchKey]['receiver'];
            $giverRealName = $pairs[$searchKey]['giver_display']; // Der Name in korrekter Groß-/Kleinschreibung
        } else {
            $message = "Dieser Name wurde nicht auf der Liste gefunden. Tippfehler?";
            $msgType = 'error';
        }
    } else {
        $message = "Keine Wichtel-Daten gefunden. Bitte Setup durchführen.";
        $msgType = 'error';
    }
}

// --- STATUS PRÜFEN ---
$isSetupMode = !file_exists($dataFile);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wichteln 2025</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Mountains+of+Christmas:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" href="gift.png">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #1a4731; /* Tannengrün */
            background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' viewBox='0 0 20 20' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%232f5e43' fill-opacity='0.4' fill-rule='evenodd'%3E%3Ccircle cx='3' cy='3' r='3'/%3E%3Ccircle cx='13' cy='13' r='3'/%3E%3C/g%3E%3C/svg%3E");
        }
        .christmas-font {
            font-family: 'Mountains of Christmas', cursive;
        }
        .snow-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }
        .snowflake {
            position: absolute;
            top: -10px;
            color: white;
            font-size: 1em;
            animation: fall linear infinite;
        }
        @keyframes fall {
            0% { transform: translateY(-10dvh) translateX(0); opacity: 1; }
            100% { transform: translateY(100dvh) translateX(20px); opacity: 0.3; }
        }
        .card {
            z-index: 10;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="min-h-dvh flex items-center justify-center p-4">

    <!-- Schneefall Effekt -->
    <div class="snow-container" id="snow"></div>

    <div class="card max-w-md w-full bg-white rounded-xl overflow-hidden border-4 border-red-700 relative">
        <!-- Dekorative Header Leiste -->
        <div class="bg-red-700 p-4 text-center relative">
            <div class="absolute top-0 left-0 w-full h-2 bg-white opacity-20 bg-stripes"></div>
            <h1 class="text-4xl text-white font-bold christmas-font tracking-wider drop-shadow-md">
                Wichtel-O-Mat 🎁
            </h1>
        </div>

        <div class="p-8">
            <!-- NACHRICHTEN ANZEIGE -->
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg text-center border-2 <?php echo $msgType === 'error' ? 'bg-red-100 border-red-400 text-red-800' : 'bg-green-100 border-green-400 text-green-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($resultName): ?>
                <!-- ERGEBNIS ANZEIGE -->
                <div class="text-center animate-pulse mb-6">
                    <p class="text-gray-600 text-lg mb-2">Hallo <span class="font-bold text-red-700"><?php echo htmlspecialchars($giverRealName); ?></span>!</p>
                    <p class="text-xl font-medium mb-4">Du musst folgende Person beschenken:</p>
                    <div class="bg-green-700 text-white text-3xl py-6 px-4 rounded-lg shadow-inner font-bold christmas-font transform scale-100 hover:scale-105 transition-transform duration-300">
                        ✨ <?php echo htmlspecialchars($resultName); ?> ✨
                    </div>
                    <p class="mt-4 text-sm text-gray-500">Psst! Nicht weitersagen. 🤫</p>
                    
                    <a href="index.php" class="mt-6 inline-block text-red-600 hover:text-red-800 underline decoration-wavy">Zurück zur Startseite</a>
                </div>

            <?php elseif ($isSetupMode): ?>
                <!-- SETUP MODUS -->
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Einrichtung</h2>
                    <p class="text-gray-600 text-sm mb-4">Erstelle hier die Liste aller Teilnehmer. Die App lost automatisch aus.</p>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="setup">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2" for="participants">
                            Namen der Teilnehmer
                        </label>
                        <textarea 
                            id="participants" 
                            name="participants" 
                            rows="8" 
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-red-500 focus:ring-red-500 outline-none transition"
                            placeholder="Max Mustermann&#10;Lisa Müller&#10;Tom Test&#10;..."
                            required></textarea>
                        <p class="text-xs text-gray-500 mt-1">Ein Name pro Zeile. Mindestens 3 Personen.</p>
                    </div>
                    <button type="submit" class="w-full bg-red-700 hover:bg-red-800 text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition hover:scale-[1.02] active:scale-95">
                        Jetzt Auslosen & Speichern 🎲
                    </button>
                </form>

            <?php else: ?>
                <!-- ABFRAGE MODUS -->
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Wen habe ich?</h2>
                    <p class="text-gray-600 mb-4">Gib deinen Namen ein, um dein Los zu sehen.</p>
                </div>

                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="action" value="lookup">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2" for="my_name">
                            Dein Name
                        </label>
                        <input 
                            type="text" 
                            id="my_name" 
                            name="my_name" 
                            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-600 focus:ring-green-600 outline-none text-center text-lg"
                            placeholder="Vorname (kein Spitzname)"
                            required>
                    </div>
                    <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition hover:scale-[1.02] active:scale-95">
                        Wichtel anzeigen 🎅
                    </button>
                </form>

                <!--<div class="mt-8 pt-4 border-t border-gray-200 text-center">
                    <a href="?reset=1" class="text-xs text-gray-400 hover:text-red-500 transition" onclick="return confirm('Wirklich alles zurücksetzen? Die aktuellen Paarungen werden gelöscht!');">
                        Alles zurücksetzen (Admin)
                    </a>
                </div>-->
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 p-3 text-center text-xs text-gray-400 border-t">
            Wir sehen uns am 16.12.! 🎄
        </div>
    </div>

    <script>
        // Einfaches Skript für Schneefall
        document.addEventListener('DOMContentLoaded', function() {
            const snowContainer = document.getElementById('snow');
            const snowflakeChars = ['❄', '❅', '❆', '•'];
            
            function createSnowflake() {
                const flake = document.createElement('div');
                flake.classList.add('snowflake');
                flake.textContent = snowflakeChars[Math.floor(Math.random() * snowflakeChars.length)];
                
                // Zufällige Position und Größe
                flake.style.left = Math.random() * 100 + 'dvw';
                flake.style.opacity = Math.random();
                flake.style.fontSize = (Math.random() * 10 + 10) + 'px';
                
                // Zufällige Animationsdauer
                const duration = Math.random() * 5 + 5;
                flake.style.animationDuration = duration + 's';
                
                snowContainer.appendChild(flake);
                
                // Entfernen nach Animation
                setTimeout(() => {
                    flake.remove();
                }, duration * 1000);
            }
            
            setInterval(createSnowflake, 200);
        });
    </script>
</body>
</html>
