<?php
// === POCZĄTEK KONFIGURACJI ===

// WPISZ TUTAJ SWÓJ TAJNY KLUCZ API v3 BREVO
$BREVO_API_KEY = 'xkeysib-TWOJ-PRAWDZIWY-KLUCZ-API-V3-WCHODZI-TUTAJ';

// WPISZ TUTAJ E-MAIL I NAZWĘ NADAWCY (MUSZĄ BYĆ ZWERYFIKOWANE W BREVO)
$SENDER_EMAIL = 'magiazapachu@magiazapachu.com.pl'; // Twój e-mail z Brevo
$SENDER_NAME = 'Magia Zapachu';                // Nazwa Twojego sklepu

// === KONIEC KONFIGURACJI ===


// Ustaw nagłówek, że odpowiadamy w formacie JSON
header('Content-Type: application/json');

// Sprawdź, czy żądanie to POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Użyj metody POST']);
    http_response_code(405);
    exit;
}

// Odczytaj dane JSON wysłane z JavaScript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// ZMIANA: Sprawdzamy, czy dostaliśmy ID szablonu, parametry i e-mail
if (empty($data['templateId']) || !isset($data['params']) || empty($data['toEmail'])) {
    echo json_encode(['error' => 'Brakuje pól: toEmail, templateId lub params']);
    http_response_code(400);
    exit;
}

// ZMIANA: Nowa struktura danych dla Brevo (wysyłanie szablonu)
$brevoData = [
    'sender' => [
        'name' => $SENDER_NAME,
        'email' => $SENDER_EMAIL
    ],
    'to' => [
        [
            'email' => $data['toEmail'],
            'name' => $data['toName'] ?? ''
        ]
    ],
    'templateId' => (int)$data['templateId'], // Używamy ID szablonu
    'params' => $data['params']              // Używamy dynamicznych parametrów
];

// Użyj wbudowanego w PHP klienta cURL do wysłania maila
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($brevoData)); // Wysyłamy nowe dane
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'api-key: ' . $BREVO_API_KEY,
    'content-type: application/json'
]);

// Wykonaj żądanie
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Zwróć odpowiedź do JavaScript
if ($http_code >= 200 && $http_code < 300) {
    echo json_encode(['success' => true, 'data' => json_decode($response)]);
} else {
    echo json_encode(['error' => 'Błąd wysyłania e-maila.', 'details' => json_decode($response)]);
    http_response_code($http_code);
}
?>
