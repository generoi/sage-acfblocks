<?php

namespace Genero\Sage\AcfBlocks;

use Roots\Acorn\ServiceProvider;

use function Roots\config;
use function Roots\view;
use function Roots\app;
use function Roots\config_path;

class AcfBlockServiceProvider extends ServiceProvider
{
    /**
     * Register blocks.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('acfblock', AcfBlock::class);

        $this->registerBlocks();
        $this->registerStyles();

        if (config('blocks.directive')) {
            $this->attachBladeDirective();
        }
    }

    public function registerBlocks()
    {
        collect(config('blocks.blocks'))->each(function ($composerClass) {
            add_action('acf/init', function () use ($composerClass) {
                app('acfblock')->registerBlock($composerClass);
                app('acfblock')->registerFields($composerClass);
            });

            $this->addViewNamespace($composerClass);
        });

        add_action('enqueue_block_editor_assets', [$this, 'registerStyles'], 1, 100);
    }

    public function registerStyles()
    {
        if ($styles = $this->app['acfblock']->getBlockStyles()) {
            wp_register_script(
                'sage/acf-blocks.js',
                $this->app['acfblock']->uri(__DIR__ . '/acf-blocks.js'),
                ['acf-blocks', 'wp-blocks'],
                filemtime(__DIR__ . '/acf-blocks.js')
            );
            wp_localize_script('sage/acf-blocks.js', 'acfBlockStyles', $styles);
            wp_enqueue_script('sage/acf-blocks.js');
        }
    }

    public function addViewNamespace($composerClass)
    {
        $hint = dirname((new \ReflectionClass($composerClass))->getFileName()) . '/views';
        $this->app['view']->addNamespace($composerClass::namespace(), $hint);
    }

    public function attachBladeDirective()
    {
        $blade = view()->getEngineResolver()->resolve('blade')->getCompiler();
        $blade->directive('acfblock', function ($expression) {
            $expression = collect(explode(',', $expression, 2))
                ->map(function ($argument) {
                    return trim($argument);
                });

            $block = "<?php \$block = acf_get_block_type({$expression->get(0)}); ?>";

            if (!empty($expression->get(1))) {
                $block .= "<?php \$block = array_merge(\$block, {$expression->get(1)}); ?>";
            }

            return $block .
                "<?php acf_render_block(\$block); ?>" .
                "<?php wp_reset_postdata(); ?>";
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/blocks.php' => config_path('blocks.php'),
        ], 'config');
    }
}
