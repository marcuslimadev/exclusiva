<?php
date_default_timezone_set('America/Sao_Paulo');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'exclusiva');
define('DB_USER', 'root');
define('DB_PASS', '');

define('API_BASE', 'https://www.exclusivalarimoveis.com.br/api/v1/app/imovel');
define('API_TOKEN', '$2y$10$Lcn1ct.wEfBonZldcjuVQ.pD5p8gBRNrPlHjVwruaG5HAui2XCG9O');

define('PROJECT_ROOT', __DIR__);
define('STORAGE_DIR', __DIR__ . '/storage');
if (!is_dir(STORAGE_DIR)) { @mkdir(STORAGE_DIR, 0777, true); }
