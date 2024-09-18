<?php
/**
 * Plugin Name: Continy Module Vite Scripts Sample Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$cmScripts = null;

if (!function_exists('continy_module_vite_scripts_init')) {
    function continy_module_vite_scripts_init(): void
    {
        global $cmScripts;

        $cmScripts = new ShoplicKr\Continy\ViteScripts\Modules\Scripts(
            [
                'basePath'  => plugin_dir_path(__FILE__),
                'baseUrl'   => plugin_dir_url(__FILE__),
                'isDevMode' => 'production' !== wp_get_environment_type(),
                'prefix'    => 'cm-',
            ],
        );
    }
}

add_action('init', 'continy_module_vite_scripts_init');


if (!function_exists('continy_module_vite_scripts_test')) {
    function continy_module_vite_scripts_test(): string
    {
        global $cmScripts;

        $cmScripts
            ->enqueueViteScript('bootstrap.tsx')
            ->localize('bootstrapVars', ['foo' => 'bar', 'rootId' => 'vite-root']);

        return '<div id="vite-root"><p>VITE Root here</p></div>';
    }
}

add_shortcode('vite_scripts_test', 'continy_module_vite_scripts_test');
