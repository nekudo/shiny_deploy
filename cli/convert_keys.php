<?php

try {
    require_once __DIR__ . '/bootstrap.php';

    // @todo Convert whole thing to a class

    // @todo Check PHP version and mcrypt extension

    // Get system password:
    fwrite(STDOUT, "Please enter your system password: ");
    $oldStyle = shell_exec('stty -g');
    shell_exec('stty -echo');
    $password = rtrim(fgets(STDIN), "\n");
    shell_exec('stty ' . $oldStyle);

    // @todo Check password

    // @todo Decrypt encryption key with old/deprecated encryption method

    // @todo Encrypt encryption key with new encryption method


} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo PHP_EOL;
}
