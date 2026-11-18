#!/usr/bin/env python3
"""Create a local disposable WordPress/SQLite copy, preserving upstream."""
import hashlib
from pathlib import Path
import shutil
import zipfile

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / 'elephc/build/php-hybrid'
SITE = OUT / 'wordpress'
ARCHIVE = OUT / 'deps/plugin-sqlite-database-integration.zip'
assert hashlib.sha256(ARCHIVE.read_bytes()).hexdigest() == '8e6f281af89612bb21ec6be3000f1a7581bbaf5d0d65515a67aedfad54eb7a72'
if not SITE.exists():
    shutil.copytree(ROOT / 'upstream/wordpress', SITE)
plugins = SITE / 'wp-content/plugins'
with zipfile.ZipFile(ARCHIVE) as archive:
    for info in archive.infolist():
        dest = (plugins / info.filename).resolve()
        assert dest.is_relative_to(plugins.resolve())
    archive.extractall(plugins)
plugin = plugins / 'plugin-sqlite-database-integration'
dropin = (plugin / 'db.copy').read_text().replace('{SQLITE_IMPLEMENTATION_FOLDER_PATH}', str(plugin))
(SITE / 'wp-content/db.php').write_text(dropin)
config = '''<?php
// Isolated benchmark installation only. No shared database or mail delivery.
define('DB_NAME', 'hybrid_bench');
define('DB_USER', 'unused');
define('DB_PASSWORD', 'unused');
define('DB_HOST', 'unused');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('DB_PATH', __DIR__ . '/../site.sqlite');
define('WP_HOME', 'http://127.0.0.1:9000');
define('WP_SITEURL', 'http://127.0.0.1:9000');
define('DISABLE_WP_CRON', true);
define('WP_HTTP_BLOCK_EXTERNAL', true);
define('AUTOMATIC_UPDATER_DISABLED', true);
define('WP_AUTO_UPDATE_CORE', false);
define('WP_DEBUG', false);
define('WP_ENVIRONMENT_TYPE', 'local');
define('WP_MEMORY_LIMIT', '256M');
$table_prefix = 'hybrid_';
define('AUTH_KEY', 'isolated-hybrid-benchmark-key');
define('SECURE_AUTH_KEY', 'isolated-hybrid-benchmark-key');
define('LOGGED_IN_KEY', 'isolated-hybrid-benchmark-key');
define('NONCE_KEY', 'isolated-hybrid-benchmark-key');
define('AUTH_SALT', 'isolated-hybrid-benchmark-salt');
define('SECURE_AUTH_SALT', 'isolated-hybrid-benchmark-salt');
define('LOGGED_IN_SALT', 'isolated-hybrid-benchmark-salt');
define('NONCE_SALT', 'isolated-hybrid-benchmark-salt');
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }
require_once ABSPATH . 'wp-settings.php';
'''
(SITE / 'wp-config.php').write_text(config)
mu = SITE / 'wp-content/mu-plugins'
mu.mkdir(exist_ok=True)
shutil.copyfile(ROOT / 'experiments/php-hybrid/integration.php', mu / 'hybrid-bench.php')
print(SITE)
