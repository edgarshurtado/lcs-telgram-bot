<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ ."config.php";

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

    // Enable MySQL
    $telegram->enableMySQL($mysql_credentials);

    // Handle telegram getUpdate request
    $telegram->handleGetUpdates();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    echo $e;
}