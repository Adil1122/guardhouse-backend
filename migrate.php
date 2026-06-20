<?php
echo shell_exec('cd ' . __DIR__ . ' && php artisan migrate --force 2>&1');
