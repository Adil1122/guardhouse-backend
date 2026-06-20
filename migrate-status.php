<?php
echo '<pre>';
echo shell_exec('cd ' . __DIR__ . ' && php artisan migrate:status 2>&1');
echo '</pre>';
