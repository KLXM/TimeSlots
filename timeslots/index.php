<?php
session_start();

// Configuration
$CONFIG = [
    'admin_username' => 'admin',
    'admin_password' => 'yourpass', // Bitte ändern Sie das Passwort!
    'calendar_token' => 'YOUR_SECURE_TOKEN_HERE', // Ersetzen Sie dies durch einen sicheren Token

    'event_start' => '2025-03-01',
    'event_end' => '2025-03-03',
    'slot_duration' => 60, // minutes
    'start_time' => '09:00',
    'end_time' => '17:00',
    'json_file' => 'bookings.json',
    'languages' => ['en', 'de'],
    'default_lang' => 'en'
];

// Translations
$TRANSLATIONS = [
    'en' => [
        'title' => 'Trade Fair Booking System',
        'name' => 'Name',
        'country' => 'Country',
        'language' => 'Preferred Language',
        'topic' => 'Meeting Topic',
        'book_slot' => 'Book Slot',
        'booking_success' => 'Booking successful! Confirmation email sent.',
        'slot_taken' => 'Sorry, this slot is already taken.',
        'admin_title' => 'Booking Administration',
        'submit' => 'Submit',
        'select_date' => 'Select Date',
        'time_slot' => 'Time Slot',
        'email' => 'Email',
        'company' => 'Company'
    ],
    'de' => [
        'title' => 'Messetermin-Buchungssystem',
        'name' => 'Name',
        'country' => 'Land',
        'language' => 'Bevorzugte Sprache',
        'topic' => 'Gesprächsthema',
        'book_slot' => 'Termin buchen',
        'booking_success' => 'Buchung erfolgreich! Bestätigungsmail wurde versandt.',
        'slot_taken' => 'Entschuldigung, dieser Termin ist bereits vergeben.',
        'admin_title' => 'Buchungsverwaltung',
        'submit' => 'Absenden',
        'select_date' => 'Datum wählen',
        'time_slot' => 'Zeitfenster',
        'email' => 'E-Mail',
        'company' => 'Firma'
    ]
];

// iCal generation function
function generateICS($bookings) {
    $ics = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//Trade Fair Booking System//EN\r\n";
    $ics .= "CALSCALE:GREGORIAN\r\n";
    $ics .= "METHOD:PUBLISH\r\n";

    foreach ($bookings as $booking) {
        $startDateTime = new DateTime($booking['date'] . ' ' . $booking['time']);
        $endDateTime = clone $startDateTime;
        $endDateTime->modify('+1 hour');

        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:" . md5($booking['date'] . $booking['time'] . $booking['email']) . "@tradefair.com\r\n";
        $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ics .= "DTSTART:" . $startDateTime->format('Ymd\THis') . "\r\n";
        $ics .= "DTEND:" . $endDateTime->format('Ymd\THis') . "\r\n";
        $ics .= "SUMMARY:Meeting with " . $booking['name'] . " (" . $booking['company'] . ")\r\n";
        $ics .= "DESCRIPTION:Topic: " . str_replace("\n", "\\n", $booking['topic']) .
                "\\nCountry: " . $booking['country'] .
                "\\nLanguage: " . $booking['language'] .
                "\\nEmail: " . $booking['email'] . "\r\n";
        $ics .= "END:VEVENT\r\n";
    }

    $ics .= "END:VCALENDAR\r\n";
    return $ics;
}

// Helper functions
function getCurrentLang() {
    global $CONFIG;
    return $_SESSION['lang'] ?? $CONFIG['default_lang'];
}

function t($key) {
    global $TRANSLATIONS;
    $lang = getCurrentLang();
    return $TRANSLATIONS[$lang][$key] ?? $key;
}

function loadBookings() {
    global $CONFIG;
    if (file_exists($CONFIG['json_file'])) {
        return json_decode(file_get_contents($CONFIG['json_file']), true) ?? [];
    }
    return [];
}

function saveBooking($booking) {
    global $CONFIG;
    $bookings = loadBookings();
    $bookings[] = $booking;
    file_put_contents($CONFIG['json_file'], json_encode($bookings, JSON_PRETTY_PRINT));
}

function isSlotAvailable($date, $time) {
    $bookings = loadBookings();
    foreach ($bookings as $booking) {
        if ($booking['date'] === $date && $booking['time'] === $time) {
            return false;
        }
    }
    return true;
}

function sendConfirmationEmail($booking) {
    $to = $booking['email'];
    $subject = 'Booking Confirmation - Trade Fair';
    $message = "Dear {$booking['name']},\n\n";
    $message .= "Your booking has been confirmed for:\n";
    $message .= "Date: {$booking['date']}\n";
    $message .= "Time: {$booking['time']}\n";
    $message .= "Topic: {$booking['topic']}\n\n";
    $message .= "Thank you for your booking!";

    mail($to, $subject, $message);
}

// Handle iCal export
if (isset($_GET['export']) && $_GET['export'] === 'ical') {
    $isAuthenticated = false;

    // Check for admin session or valid token
    if (isset($_SESSION['admin']) ||
        (isset($_GET['token']) && $_GET['token'] === $CONFIG['calendar_token'])) {
        $isAuthenticated = true;
    }

    if ($isAuthenticated) {
        $bookings = loadBookings();
        $ics = generateICS($bookings);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=trade-fair-meetings.ics');
        echo $ics;
        exit;
    } else {
        $_SESSION['error'] = 'Unauthorized access';
    }
}

// Datum aus POST abrufen
$selectedDate = $_POST['date'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['book_slot'])) {
        $booking = [
            'date' => $_POST['date'],
            'time' => $_POST['time'],
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'company' => $_POST['company'],
            'country' => $_POST['country'],
            'language' => $_POST['language'],
            'topic' => $_POST['topic'],
            'timestamp' => time()
        ];

        if (isSlotAvailable($booking['date'], $booking['time'])) {
            saveBooking($booking);
            sendConfirmationEmail($booking);
            $_SESSION['message'] = t('booking_success');
        } else {
            $_SESSION['error'] = t('slot_taken');
        }
    }
}

// Language switcher
if (isset($_GET['lang']) && in_array($_GET['lang'], $CONFIG['languages'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('title'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .hidden-by-default { display: none; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Language Switcher -->
        <div class="mb-4 text-right">
            <?php foreach ($CONFIG['languages'] as $lang): ?>
                <a href="?lang=<?php echo $lang; ?>" class="inline-block px-2 py-1 bg-blue-500 text-white rounded">
                    <?php echo strtoupper($lang); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <h1 class="text-3xl font-bold mb-8"><?php echo t('title'); ?></h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php
        // Admin Login handling
        if (isset($_POST['admin_login'])) {
            if ($_POST['username'] === $CONFIG['admin_username'] &&
                $_POST['password'] === $CONFIG['admin_password']) {
                $_SESSION['admin'] = true;
            } else {
                $_SESSION['error'] = 'Invalid credentials';
            }
        }

        if (isset($_GET['logout'])) {
            unset($_SESSION['admin']);
        }

        // Admin View
        if (isset($_SESSION['admin'])): ?>
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold"><?php echo t('admin_title'); ?></h2>
                    <div class="space-x-2">
                        <?php
                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
                        $calendarUrl = $baseUrl . '?export=ical&token=' . $CONFIG['calendar_token'];
                        ?>
                        <a href="<?php echo $baseUrl; ?>?export=ical"
                           class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Export iCal
                        </a>
                        <a href="#"
                           onclick="copyCalendarUrl('<?php echo $calendarUrl; ?>')"
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Copy Calendar URL
                        </a>
                        <a href="?logout=1" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Instructions Modal -->
                <div id="calendarInstructions" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3 text-center">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Kalender abonnieren</h3>
                            <div class="mt-2 px-7 py-3">
                                <p class="text-sm text-gray-500">
                                    So fügen Sie den Kalender hinzu:
                                </p>
                                <ol class="text-left text-sm text-gray-500 list-decimal pl-4 mt-2">
                                    <li>Kopieren Sie die URL (bereits in Ihrer Zwischenablage)</li>
                                    <li>Öffnen Sie Ihr Kalenderprogramm</li>
                                    <li>Wählen Sie "Kalender hinzufügen" oder "Abonnieren"</li>
                                    <li>Fügen Sie die kopierte URL ein</li>
                                </ol>
                            </div>
                            <div class="items-center px-4 py-3">
                                <button id="closeModal" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    Verstanden
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                function copyCalendarUrl(url) {
                    navigator.clipboard.writeText(url).then(() => {
                        document.getElementById('calendarInstructions').classList.remove('hidden');
                    });
                }

                document.getElementById('closeModal').addEventListener('click', () => {
                    document.getElementById('calendarInstructions').classList.add('hidden');
                });
                </script>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white shadow-md rounded">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-4 border-b text-left">Datum</th>
                                <th class="py-2 px-4 border-b text-left">Zeit</th>
                                <th class="py-2 px-4 border-b text-left">Name</th>
                                <th class="py-2 px-4 border-b text-left">Firma</th>
                                <th class="py-2 px-4 border-b text-left">E-Mail</th>
                                <th class="py-2 px-4 border-b text-left">Land</th>
                                <th class="py-2 px-4 border-b text-left">Sprache</th>
                                <th class="py-2 px-4 border-b text-left">Thema</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $bookings = loadBookings();
                            usort($bookings, function($a, $b) {
                                $dateCompare = strcmp($a['date'], $b['date']);
                                if ($dateCompare === 0) {
                                    return strcmp($a['time'], $b['time']);
                                }
                                return $dateCompare;
                            });

                            foreach ($bookings as $booking): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['date']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['time']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['name']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['company']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['email']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['country']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['language']); ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($booking['topic']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Admin Login Form -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold mb-4">Admin Login</h2>
                <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Username
                        </label>
                        <input type="text" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Password
                        </label>
                        <input type="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <button type="submit" name="admin_login" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Login
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    <?php echo t('select_date'); ?>
                </label>
                <input type="text" name="date" class="flatpickr shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required value="<?php echo htmlspecialchars($selectedDate ?? ''); ?>">
            </div>

            <?php if ($selectedDate): ?>
              <div class="mb-4" id="timeslot-container">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                      <?php echo t('time_slot'); ?>
                  </label>
                  <select name="time" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                      <?php
                      $start = strtotime($CONFIG['start_time']);
                      $end = strtotime($CONFIG['end_time']);
                      $interval = $CONFIG['slot_duration'] * 60;

                      for ($time = $start; $time < $end; $time += $interval) {
                          $timeStr = date('H:i', $time);
                          $isAvailable = isSlotAvailable($selectedDate, $timeStr);

                          if ($isAvailable) {
                              echo "<option value=\"$timeStr\">$timeStr</option>";
                          }
                      }
                      ?>
                  </select>
              </div>

              <div class="mb-4" id="name-container">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                      <?php echo t('name'); ?>
                  </label>
                  <input type="text" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
              </div>

              <div class="mb-4" id="email-container">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                      <?php echo t('email'); ?>
                  </label>
                  <input type="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
              </div>

              <div class="mb-4" id="company-container">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                      <?php echo t('company'); ?>
                  </label>
                  <input type="text" name="company" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
              </div>

              <div class="mb-4" id="country-container">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                      <?php echo t('country'); ?>
                  </label>
                  <input type="text" name="country" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
              </div>

              <div class="mb-4" id="language-container">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                      <?php echo t('language'); ?>
                  </label>
                  <select name="language" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                          <?php foreach ($CONFIG['languages'] as $lang): ?>
                              <option value="<?php echo $lang; ?>"><?php echo strtoupper($lang); ?></option>
                          <?php endforeach; ?>
                  </select>
              </div>

              <div class="mb-6" id="topic-container">
                  <label class="block text-gray-700 text-sm font-bold mb-2">
                      <?php echo t('topic'); ?>
                  </label>
                  <textarea name="topic" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
              </div>

              <div class="flex items-center justify-between" id="submit-container">
                  <button type="submit" name="book_slot" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                      <?php echo t('book_slot'); ?>
                  </button>
              </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
        const datePicker = flatpickr('input[name="date"]', {
            dateFormat: 'Y-m-d',
            minDate: '<?php echo $CONFIG['event_start']; ?>',
            maxDate: '<?php echo $CONFIG['event_end']; ?>',
            disable: [
                function(date) {
                    return (date.getDay() === 0 || date.getDay() === 6);
                }
            ],
            onChange: function(selectedDates, dateStr) {
                this._input.form.submit();
            }
        });
    </script>
</body>
</html>
