<?php
echo shell_exec('cd ' . __DIR__ . ' && php artisan route:clear && php artisan config:clear && php artisan cache:clear 2>&1');
