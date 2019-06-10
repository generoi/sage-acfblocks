<?php

namespace Genero\Sage\AcfBlocks;

use Illuminate\View\View;
use function Roots\view;

abstract class Block
{
    /** @var array The block settings and attributes. */
    protected static $register;

    /** @var string Machine name of block. */
    protected $name;

    /** @var array The block settings and attributes. */
    protected $block;

    /** @var string The block inner HTML. */
    protected $content;

    /** @var bool If the block is being previewed. */
    protected $isPreview;

    /** @var int The post ID this block is saved to. */
    protected $postId;

    /** @var string The block style this block is displayed with. */
    protected $style;

    /** @var string View namespace. */
    protected $namsepace;

    /**
     * Block registration settings.
     *
     * @link https://www.advancedcustomfields.com/resources/acf_register_block_type/
     */
    public static function register(): array
    {
        if (static::$register) {
            return static::$register;
        }

        return [];
    }

    /**
     * Register ACF fields.
     */
    public static function fields(): array
    {
        return [];
    }

    /**
     * View namespace.
     */
    public static function namespace(): string
    {
        return (new \ReflectionClass(get_called_class()))->getShortName();
    }

    /**
     * @param string $name The name of the block
     * @param array $block The block settings and attributes.
     * @param string $content The block inner HTML (empty).
     * @param bool $isPreview True during AJAX preview.
     * @param int $postId The post ID this block is saved to.
     * @return \App\Blocks\Block
     */
    public function __construct(string $name, array $block, string $content, bool $isPreview, int $postId)
    {
        $this->name = $name;
        $this->block = $block;
        $this->content = $content;
        $this->isPreview = $isPreview;
        $this->postId = $postId;
        $this->style = $this->gatherStyle($block['className'] ?? '');
        $this->namespace = self::namespace();
    }

    /**
     * List of views for this block, where the first found will be used.
     */
    public function views(): array
    {
        return [
            "{$this->namespace}::{$this->name}-{$this->style}",
            "{$this->namespace}::{$this->name}",
        ];
    }

    /**
     * Get the style name this block uses.
     */
    protected function gatherStyle(string $className): string
    {
        return preg_match('/is-style-([^\s]+)/', $className, $matches) ? $matches[1] : 'default';
    }

    /**
     * If the block is being previewed.
    */
    public function isPreview(): bool
    {
        return $this->isPreview;
    }

    /**
     * The block settings and attributes.
     */
    public function getBlock(string $key = null): array
    {
        return $this->block;
    }

    /**
     * Get the block classes.
     */
    protected function getClasses(): array
    {
        return collect(explode(' ', $this->block['className'] ?? ''))
            ->prepend(str_replace('/', '-', $this->block['name']))
            ->push(!empty($this->block['align']) ? 'align' . $this->block['align'] : null)
            ->flatten()
            ->filter()
            ->all();
    }

    /**
     * The block style this block is displayed with.
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * The post ID this block is saved to.
     */
    public function getPostId(): int
    {
        return $this->postId;
    }

    /**
     * The block inner HTML.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * View used for rendering the block.
     */
    public function compose(View $view): void
    {
        $view->with($this->defaults());
        $view->with(array_merge(
            $this->with($view->getData(), $view),
            $view->getData(),
            $this->override($view->getData(), $view)
        ));
    }

    /**
     * Default data avilable in the block.
     */
    public function defaults(): array
    {
        return [
            'classes' => implode(' ', $this->getClasses()),
            'is_preview' => $this->isPreview(),
            'style' => $this->getStyle(),
            'post_id' => $this->getPostId(),
            'content' => $this->getContent(),
        ];
    }

    /**
     * Get the string contents of the view.
     */
    public function render(View $view): string
    {
        return $view->render();
    }

    /**
     * Data to be passed to the rendered block.
     */
    public function override($data, $view)
    {
        return [];
    }

    /**
     * Data to be passed to the rendered block.
     */
    public function with($data, $view)
    {
        return [];
    }
}
