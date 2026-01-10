<?php
/**
 * Header для CRM системы
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM ProfTransfer - Система управления транспортом</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Базовые стили */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        .container {
            display: flex;
            min-height: calc(100vh - 80px);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚗 CRM ProfTransfer</h1>
    </div>
    <div class="container">