<?php

namespace Genero\Sage\AcfBlocks;

use Illuminate\Support\ServiceProvider;

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

        if ($this->app->config->get('blocks.directive')) {
            $this->attachBladeDirective();
        }
    }

    public function registerBlocks()
    {
        collect($this->app->config->get('blocks.blocks'))->each(function ($composerClass) {
            add_action('acf/init', function () use ($composerClass) {
                $this->app['acfblock']->registerBlock($composerClass);
                $this->app['acfblock']->registerFields($composerClass);
            });

            $this->addViewNamespace($composerClass);
        });

        add_action('enqueue_block_editor_assets', [$this, 'registerStyles'], 1, 100);
    }

    public function registerStyles()
    {
        if ($styles = $this->app['acfblock']->getBlockStyles()) {
            foreach ($styles as $blockname => $allstyles) {
                foreach ($allstyles as $key => $style) {
                    register_block_style($blockname, $style);
                }
            }
        }
    }

    public function addViewNamespace($composerClass)
    {
        $hint = dirname((new \ReflectionClass($composerClass))->getFileName()) . '/views';
        $this->app['view']->addNamespace($composerClass::namespace(), $hint);
    }

    public function attachBladeDirective()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();
        $blade->directive('acfblock', function ($expression) {
            $expression = collect(explode(',', $expression, 2))
                ->map(function ($argument) {
                    return trim($argument);
                });

            $block = "<?php \$block = acf_get_block_type({$expression->get(0)}); ?>";

            if (!empty($expression->get(1))) {
                $block .= "<?php \$block = array_merge(\$block, {$expression->get(1)}); ?>";
            }
            $block .= "<?php \$block = array_merge(\$block, ['id' => acf_get_block_id(\$block)]); ?>";

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
            __DIR__ . '/../config/blocks.php' => $this->app->configPath('blocks.php'),
        ], 'config');
    }
}
