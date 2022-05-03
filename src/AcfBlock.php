<?php

namespace Genero\Sage\AcfBlocks;

use Illuminate\Support\Arr;

class AcfBlock
{
    /** @var array Registered blocks. */
    protected $blocks = [];
    protected $styles = [];

    public function registerBlock(string $composer): void
    {
        $settings = $this::getSettings($composer);
        $this->set("$composer.settings", $settings);

        if (!empty($settings['styles'])) {
            $this->addBlockStyles("acf/{$settings['name']}", $settings['styles']);
            unset($settings['styles']);
        }

        acf_register_block(array_merge([
            'render_callback' => function ($block, $content = '', $isPreview = false, $postId = 0) use ($composer) {
                $this->set("$composer.composer", new $composer(
                    $this->get("$composer.settings.name"),
                    $block,
                    $content,
                    is_bool($isPreview) ? $isPreview : false,
                    (int) $postId
                ));

                $this->renderBlock($this->get("$composer.composer"));
            },
        ], $settings));
    }

    public function renderBlock(Block $composer)
    {
        try {
            $view = $this->app['view']->first($composer->views());
        } catch (\InvalidArgumentException $e) {
            if ($composer->isPreview()) {
                echo sprintf('<div>%s</div', $e->getMessage());
                return;
            }
            throw $e;
        }

        $composer->compose($view);
        echo $composer->render($view);
    }

    public function getSettings(string $composer): ?array
    {
        $data = $composer::register();
        if (!$data) {
            return null;
        }
        // Unless assets are URLs, prefix them with the URL to the block directory.
        foreach (['enqueue_style', 'enqueue_script'] as $attribute) {
            if (!empty($data[$attribute]) && !filter_var($data[$attribute], FILTER_VALIDATE_URL)) {
                $path = dirname((new \ReflectionClass($composer))->getFileName());
                $data[$attribute] = $this->uri($path . '/' . $data[$attribute]);
            }
        }

        return $data;
    }

    public function registerFields(string $composer): void
    {
        $fields = $composer::fields();
        if (empty($fields)) {
            return;
        }

        acf_add_local_field_group($fields);
    }

    public function uri(string $path): string
    {
        return str_replace(
            get_theme_file_path(),
            get_template_directory_uri(),
            $path
        );
    }

    public function addBlockStyles(string $block, array $styles): void
    {
        $this->styles[$block] = $styles;
    }

    public function getBlockStyles(): array
    {
        return collect($this->styles)
            ->map(function ($styles) {
                return collect($styles)
                    ->map(function ($label, $name) {
                        return ['name' => $name, 'label' => $label];
                    })
                    ->values()
                    ->all();
            })
            ->all();
    }

    public function get(string $key = null, $default = null)
    {
        return Arr::get($this->blocks, $key, $default);
    }

    public function set(string $key, $value = null)
    {
        return Arr::set($this->blocks, $key, $value);
    }
}
