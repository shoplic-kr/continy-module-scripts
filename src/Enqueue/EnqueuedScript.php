<?php

namespace ShoplicKr\Continy\ViteScripts\Enqueue;

use ShoplicKr\Continy\ViteScripts\Modules\Scripts;
use function Shoplic\MedicalObserver\Helpers\getThemeBaseUrl;

class EnqueuedScript
{
    public function __construct(
        private string  $handle,
        private array   $deps,
        private array   $args,
        private Scripts $module,
    )
    {
        if (wp_script_is($handle)) {
            return;
        }

        if ($this->module->isDevMode()) {
            $this->developEnqueue();
        } else {
            $this->productionEnqueue();
        }
    }

    public function localize(string $objectName, array $values): Scripts
    {
        if ($objectName && $values) {
            $handle = $this->module->getPrefix() . $this->handle;
            wp_localize_script($handle, $objectName, $values);
        }

        return $this->module;
    }

    private function developEnqueue(): void
    {
        $handle = $this->module->getPrefix() . $this->handle;
        $url    = $this->module->getDevServerUrl() . $this->module->getSrcRoot() . $this->handle;
        $deps   = [
            'wp-ii18n',
            $this->module->getPrefix() . 'dev-vite-client',
            ...$this->deps,
        ];
        $args   = [
            'in_footer' => true,
            'strategy'  => 'async',
            ...$this->args,
        ];

        wp_enqueue_script($handle, $url, $deps, null, $args);
        wp_add_inline_script($handle, "console.info('$handle is running in development mode.')");
    }

    private function productionEnqueue(): void
    {
        $manifest = $this->module->getManifest();
        $handle   = $this->module->getPrefix() . $this->handle;
        $key      = $this->module->getSrcRoot() . $handle;
        $isEntry  = $this->manifest[$key]['isEntry'] ?? false;
        $src      = $isEntry ? $manifest[$key]['file'] : '';
        $cssItems = $isEntry && isset($manifest[$key]['css']) ? $manifest[$key]['css'] : [];

        if ($src) {
            $url  = $this->module->getBaseUrl() . $this->module->getBuildRoot() . $src;
            $deps = $this->deps;
            $args = [
                'in_footer' => true,
                'strategy'  => 'async',
                ...$this->args,
            ];
            wp_enqueue_script($handle, $url, $deps, null, $args);
        }

        $imports = $manifest[$key]['imports'] ?? [];
        reset($imports);
        while (($import = next($imports))) {
            if (isset($this->manifest[$import]['css'])) {
                $cssItems = [...$cssItems, ...$this->manifest[$import]['css']];
            }
            $nestedImports = $this->manifest[$import]['imports'] ?? [];
            foreach ($nestedImports as $nestedImport) {
                $imports[] = $nestedImport;
            }
        }

        foreach ($cssItems as $item) {
            if ($item) {
                $url = $this->module->getBaseUrl() . $this->module->getBuildRoot() . $item;
                wp_enqueue_style($item, $url, [], null);
            }
        }
    }
}
