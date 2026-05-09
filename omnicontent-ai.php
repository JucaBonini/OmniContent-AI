<?php
/**
 * Plugin Name: OmniContent AI: Universal GEO & SEO Automator
 * Plugin URI:  https://capitalconsciente.com.br
 * Description: The definitive AI content engine for the GEO/AEO era. Multi-niche, intent-aware, and built for high-authority SEO.
 * Version:     2.0.1
 * Author:      OmniContent Team
 * License:     GPLv2 or later
 * Text Domain: omnicontent-ai
 */

if (!defined('ABSPATH')) {
    exit;
}

// Global Constants - OCAI Prefix
define('OCAI_VERSION', '2.0.1');
define('OCAI_PATH', plugin_dir_path(__FILE__));
define('OCAI_URL', plugin_dir_url(__FILE__));

// Autoloader Simples
spl_autoload_register(function ($class) {
    $prefix = 'OmniContentAI\\';
    $base_dir = OCAI_PATH . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Inicialização
add_action('plugins_loaded', function() {
    new \OmniContentAI\Admin\Settings();
    new \OmniContentAI\Admin\Keywords();
    new \OmniContentAI\Core\Ajax();
});
