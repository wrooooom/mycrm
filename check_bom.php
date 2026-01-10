<?php
function checkBOM($filename) {
    $contents = file_get_contents($filename);
    $bom = pack('H*','EFBBBF');
    $hasBOM = (substr($contents, 0, 3) === $bom);
    
    echo "Файл: $filename - " . ($hasBOM ? "❌ ЕСТЬ BOM" : "✅ OK") . "<br>";
    
    if ($hasBOM) {
        // Удаляем BOM
        $contents = substr($contents, 3);
        file_put_contents($filename, $contents);
        echo "BOM удален из $filename<br>";
    }
}

$files = ['config.php', 'auth.php', 'applications.php', 'index.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        checkBOM($file);
    }
}
?>