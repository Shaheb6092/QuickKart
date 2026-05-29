<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Quick Kart</title>
    <!-- <title>Shop Mix</title> -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            -webkit-user-select: none; /* Safari */
            -moz-user-select: none; /* Firefox */
            -ms-user-select: none; /* IE10+/Edge */
            user-select: none; /* Standard */
            -webkit-tap-highlight-color: transparent;
        }
    </style>
   
    <!-- FAVICONS: added so favicon appears on all pages -->
    <link rel="apple-touch-icon" sizes="180x180" href="./favicon/logo3.png">
    <link rel="icon" type="image/png" sizes="32x32" href="./favicon/logo2.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./favicon/logo1.png">
    <link rel="shortcut icon" href="./favicon/logo3.png">
    <link rel="manifest" href="./favicon/logo3.png">
    <link rel="mask-icon" href="./favicon/logo3.png" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#2b5797">
    <meta name="msapplication-config" content="/favicon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

</head>
<body class="bg-gray-100 antialiased font-sans text-gray-800 overflow-x-hidden">
    <div id="app-container" class="relative min-h-screen pb-20">
        <!-- Loading Modal -->
        <div id="loading-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white p-5 rounded-lg flex items-center space-x-3">
                <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-indigo-500"></div>
                <span class="font-medium text-gray-700">Loading...</span>
            </div>
        </div>

        <!-- Top Header -->
        <header class="bg-white shadow-md sticky top-0 z-40 px-4 py-3 flex items-center justify-between">
            <button id="menu-btn" class="text-gray-600 text-xl">
                <i class="fas fa-bars"></i>
            </button>
            <div class="flex items-center">
                <i class="fas fa-shopping-cart text-indigo-600 text-2xl mr-2"></i>
                <a href="index.php" class="text-xl font-bold text-gray-800">Quick Kart</a>
            </div>
            <a href="cart.php" class="text-gray-600 text-xl relative">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                    <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                </span>
            </a>
        </header>


