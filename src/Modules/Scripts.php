<?php

namespace ShoplicKr\Continy\ViteScripts\Modules;

use ShoplicKr\Continy\Contract\Module;
use ShoplicKr\Continy\ViteScripts\Enqueue\EnqueuedScript;

class Scripts implements Module
{
    /**
     * Base path
     */
    private string $basePath;

    /**
     * Base url
     */
    private string $baseUrl;

    /**
     * Your transpiled scripts root, defaults to 'build/'
     *
     * @var string
     */
    private string $buildRoot;

    /**
     * Vite server port, defaults to 5173
     *
     * @var int
     */
    private int $devServerPort;

    /**
     * Scrips are running in development mode, or production mode.
     *
     * @var bool
     */
    private bool $isDevMode;

    /**
     * Vite manifest.
     *
     * @var array
     */
    private array $manifest;

    /**
     * Your script prefix, defaults to empty string
     *
     * @var string
     */
    private string $prefix;

    /**
     * Your js/ts src root, defaults to 'src/'
     *
     * @var string
     */
    private string $srcRoot;

    /**
     * Catch our VITE development script handles
     *
     * @var array<string, true>
     */
    private array $handles;

    public function __construct(array $args = [])
    {
        $args = wp_parse_args(
            $args,
            [
                'basePath'      => '',
                'baseUrl'       => '',
                'buildRoot'     => 'build/',
                'configPath'    => '',
                'devServerPort' => 5173,
                'isDevMode'     => false,
                'prefix'        => '',
                'srcRoot'       => 'src/',
            ],
        );

        $this->basePath      = $args['basePath'];
        $this->baseUrl       = untrailingslashit($args['baseUrl']);
        $this->buildRoot     = trailingslashit($args['buildRoot']);
        $this->devServerPort = (int)$args['devServerPort'];
        $this->handles       = [];
        $this->isDevMode     = (bool)$args['isDevMode'];
        $this->prefix        = $args['prefix'];
        $this->srcRoot       = trailingslashit($args['src']);

        $this->loadManifest();
        $this->registerViteDevScripts();
        $this->registerScripts($args['configPath']);
    }

    public function enqueueViteScript(string $entry, array $deps = [], array|bool $args = []): EnqueuedScript
    {
        return new EnqueuedScript($entry, $deps, $args, $this);
    }

    public function addHandle(string $handle): void
    {
        $this->handles[] = $handle;
    }

    public function filterScriptLoaderTag(string $tag, string $handle): string
    {
        if (in_array($handle, $this->handles, true)) {
            // <script> tag can be found more than once
            // if wp_add_inline_script() is called.
            $lastPos = 0;
            $scripts = [];

            do {
                $pos = strpos($tag, '<script ', $lastPos + 1);
                if ($pos > $lastPos) {
                    $scripts[] = trim(substr($tag, $lastPos, $pos - $lastPos));
                    $lastPos   = $pos;
                }
            } while ($pos !== false);

            $rest = trim(substr($tag, $lastPos));
            if (str_starts_with($rest, '<script')) {
                $scripts[] = trim($rest);
                $rest      = '';
            }

            foreach ($scripts as &$script) {
                if (str_starts_with($script, '<script ')) {
                    $attrs = substr($script, 6, strpos($script, '>') - 6);
                    if (!str_contains($attrs, 'src=')) {
                        continue;
                    }

                    $replace = '<script ';
                    $type    = false;

                    preg_match_all(
                        '/(\w+)=["\']?((?:.(?!["\']?\s+\S+=|\s*\/?[>"\']))+.)["\']?/',
                        $attrs,
                        $matches,
                        PREG_SET_ORDER,
                    );

                    foreach ($matches as $match) {
                        if ('type' === $match[1]) {
                            $replace .= " type='module'";
                            $type    = true;
                        } else {
                            $replace .= " $match[0]";
                        }
                    }

                    if (!$type) {
                        $replace .= " type='module'";
                    }

                    $replace .= '></script>' . PHP_EOL;

                    $script = $replace;
                }
            }

            $tag = implode(PHP_EOL, $scripts) . $rest . PHP_EOL;
        }

        return $tag;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getBuildRoot(): string
    {
        return $this->buildRoot;
    }

    public function getDevServerUrl(): string
    {
        return trailingslashit("http://localhost:$this->devServerPort");
    }

    public function getManifest(): array
    {
        return $this->manifest;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getSrcRoot(): string
    {
        return $this->srcRoot;
    }

    public function isDevMode(): bool
    {
        return $this->isDevMode;
    }

    private function loadManifest(): void
    {
        $manifestPath = $this->getBasePath() . $this->getBuildRoot() . '.vite/manifest.json';

        if (file_exists($manifestPath) && is_file($manifestPath) && is_readable($manifestPath)) {
            $content        = file_get_contents($manifestPath) ?: '';
            $manifest       = json_decode($content, true) ?: [];
            $this->manifest = $manifest;
        } else {
            $this->manifest = [];
        }
    }

    private function registerViteDevScripts(): void
    {
        if (!$this->isDevMode) {
            return;
        }

        // dev-refresh
        $version = time();
        $args    = ['in_footer' => true, 'strategy' => 'defer'];

        // dev-refresh.js
        $refresh = $this->getPrefix() . 'dev-refresh';
        $url     = $this->getBaseUrl() . $this->getSrcRoot() . '/dev-refresh.js';
        $deps    = [];

        if (!wp_script_is($refresh, 'registered')) {
            wp_register_script($refresh, $url, $deps, $version, $args);
            $this->addHandle($refresh);
        }

        // @vite/client
        $client = $this->getPrefix() . 'dev-@vite/client';
        $url    = $this->getDevServerUrl() . '@vite/client';
        $deps   = [$refresh];

        if (!wp_script_is($client, 'registered')) {
            wp_register_script($client, $url, $deps, $version);
            $this->addHandle($client);
        }

        if (!has_filter('script_loader_tag', [$this, 'filterScriptLoaderTag'])) {
            add_filter('script_loader_tag', [$this, 'filterScriptLoaderTag'], 9999, 3);
        }
    }

    /**
     * Load array from config path, and register all scripts and styles.
     *
     * @param string $configPath
     *
     * @return void
     */
    private function registerScripts(string $configPath): void
    {
        if (empty($configPath) || !file_exists($configPath) || !is_readable($configPath)) {
            return;
        }

        $config  = include $configPath;
        $scripts = (array)$config['scripts'] ?? [];
        $styles  = (array)$config['styles'] ?? [];

        foreach ($scripts as $item) {
            $item = wp_parse_args(
                $item,
                [
                    'handle' => '',
                    'src'    => '',
                    'deps'   => [],
                    'ver'    => false,
                    'args'   => [
                        'in_footer' => false,
                        // 'strategy'  => 'defer|async',
                    ],
                ],
            );

            if ($item['handle'] && $item['src']) {
                wp_enqueue_script($item['handle'], $item['src'], $item['deps'], $item['ver'], $item['args']);
            }
        }

        foreach ($styles as $item) {
            $item = wp_parse_args(
                $item,
                [
                    'handle' => '',
                    'src'    => '',
                    'deps'   => [],
                    'ver'    => false,
                    'media'  => 'all',
                ],
            );

            if ($item['handle'] && $item['src']) {
                wp_enqueue_style($item['handle'], $item['src'], $item['deps'], $item['ver'], $item['media']);
            }
        }
    }
}
