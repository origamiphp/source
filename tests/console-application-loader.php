<?php

require \dirname(__DIR__).'/../config/bootstrap.php';
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

return new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
