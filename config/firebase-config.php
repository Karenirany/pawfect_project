<?php
require_once __DIR__ . '/../../vendor/autoload.php'; // Path to Firebase PHP SDK

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// Firebase configuration
$firebaseConfig = [
    'apiKey' => "AIzaSyASCrVkQ_P7JLu79vXt9rTFYCpiu4cGRhU",
    'authDomain' => "pawfect-b4bc1.firebaseapp.com",
    'projectId' => "pawfect-b4bc1",
    'storageBucket' => "pawfect-b4bc1.firebasestorage.app",
    'messagingSenderId' => "383970177268",
    'appId' => "1:383970177268:web:a28df5b8f66d11d778eea1"
];

// Initialize Firebase
$serviceAccount = ServiceAccount::fromJsonFile(__DIR__ . '/../../serviceAccountKey.json');
$firebase = (new Factory)
    ->withServiceAccount($serviceAccount)
    ->create();

$auth = $firebase->getAuth();
$firestore = $firebase->getFirestore();
?>