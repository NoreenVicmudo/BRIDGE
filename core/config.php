<?php
// ----------------------------
// Project Configuration
// ----------------------------

// Detect protocol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";

// Host
$host = $_SERVER['HTTP_HOST'];

// Project root folder (filesystem path)
define('PROJECT_PATH', realpath(__DIR__)); // one level up from core

// Base URL for redirects
$base_url = rtrim($protocol . "://" . $host . '/' . basename(PROJECT_PATH), '/');


// Optional: Debugging
// echo "Project path: " . PROJECT_PATH . "<br>";
// echo "Base URL: " . $base_url;
