<?php
if (!isset($working_directory)) {
    die("Error: \$working_directory is not defined.\n");
}
if (!is_dir($working_directory)) {
    die("Error: The working directory '$working_directory' does not exist.\n");
}

$working_directory = rtrim($working_directory, DIRECTORY_SEPARATOR);

$theiaDir = $working_directory . DIRECTORY_SEPARATOR . 'TheiaFormatData';
if (!is_dir($theiaDir)) {
    if (!mkdir($theiaDir, 0755, true)) {
        die("Error: Could not create TheiaFormatData at '$theiaDir'.\n");
    }
}

foreach (scandir($working_directory) as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }
    $src = $working_directory . DIRECTORY_SEPARATOR . $file;

    if (preg_match('/^(.*)\.settings\.xml$/i', $file, $m)) {
        $trialDir = $theiaDir . DIRECTORY_SEPARATOR . $m[1];
        if (!is_dir($trialDir)) {
            mkdir($trialDir, 0755, true);
        }

        $dst = $trialDir . DIRECTORY_SEPARATOR . 'cal.txt';
        if (!copy($src, $dst)) {
            continue;
        }

        $raw = file_get_contents($dst);
        if ($raw === false) {
            continue;
        }

        $bom = substr($raw, 0, 2);
        if ($bom === "\xFF\xFE") {
            $enc = 'UTF-16LE';
        } elseif ($bom === "\xFE\xFF") {
            $enc = 'UTF-16BE';
        } else {
            $guess = mb_detect_encoding(
                $raw,
                ['UTF-8','UTF-16LE','UTF-16BE','ISO-8859-1','WINDOWS-1252'],
                true
            );
            $enc = $guess ?: 'UTF-8';
        }

        $utf8 = mb_convert_encoding($raw, 'UTF-8', $enc);

        $search  = ['<Settings ', '<Settings>', '<Settings', '</Settings>', '</Settings >'];
        $replace = ['<calibration ', '<calibration>', '<calibration', '</calibration>', '</calibration>'];
        $utf8 = str_ireplace($search, $replace, $utf8);

        file_put_contents($dst, $utf8);
        continue;
    }

    if (preg_match('/^(.*?)_.+?_(\d{5})\.avi$/i', $file, $m)) {
        $trialName = $m[1];
        $cameraId  = $m[2];

        $trialDir = $theiaDir . DIRECTORY_SEPARATOR . $trialName;
        if (!is_dir($trialDir)) {
            mkdir($trialDir, 0755, true);
        }

        $camDir = $trialDir . DIRECTORY_SEPARATOR . $cameraId;
        if (!is_dir($camDir)) {
            mkdir($camDir, 0755, true);
        }

        copy($src, $camDir . DIRECTORY_SEPARATOR . $cameraId . '.avi');
        continue;
    }

}
?>
