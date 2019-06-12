# [WIP] Sage ACF Blocks

A Sage 10 helper package for building ACF blocks rendered using blade templates.

The main difference between this and others similar packages is that it's designed for also rendering the blocks directly from templates that do not have a Block Editor. It also supports rendering different templates per block style.

_Note that this is still in a proof of concept stage_.

### Installation

1. Install the package in yor theme

    ```sh
    composer config repositories.sage-acfblocks vcs https://github.com/generoi/sage-acfblocks.git
    composer require generoi/sage-acfblocks:dev-master
    ```

2. Add `Generoi\Sage\AcfBlocks\BlockServiceProvider::class` to the providers in `config/app.php` or add automatically with:

    ```sh
    wp acorn package:discovery
    ```

3. Publish the default `config/blocks.php` file.

    ```sh
    wp acorn vendor:publish
    ```

### Getting started example

1. Create a directory to store your blocks.

    ```sh
    app/Blocks
    └── ContentListing
        ├── ContentListing.php
        ├── assets
        │   ├── content-listing.css
        │   └── content-listing.js
        └── views
            ├── content-listing-accordion.blade.php
            └── content-listing.blade.php
    ```

2. Configure your block in `app/Blocks/ContentListing/ContentListing.php`

    ```php
    <?php

    namespace App\Blocks\ContentListing;

    use Genero\Sage\AcfBlocks\Block;
    use Illuminate\View\View;

    class ContentListing extends Block
    {
        /** @var array The block registration settings. */
        public static $register = [
            'name' => 'content-listing',
            'title' => 'Content listing',
            'description' => 'A block listing content based on filters',
            'category' => 'sage',
            'align' => 'wide',
            'mode' => 'preview',
            'icon' => 'excerpt-view',
            'keywords' => ['post', 'query'],
            'supports' => [],

            // Unless set dynamically to a URL using the static register() method
            // these will be loaded from in the root diretory of the block.
            'enqueue_style' => 'assets/content-listing.css',
            'enqueue_script' => 'assets/content-listing.js',

            // Define block styles which will be automatically added and used
            // when looking for block templates.
            'styles' => [
                'accordion' => 'Accordion',
            ],
        ];

        /**
         * Data to be passed to the rendered block.
         */
        public function with($data, $view)
        {
            $data['fields'] = (object) array_merge([
                'posts_per_page' => 3,
                'order_by' => ['date'],
                'order' => 'DESC',
                'post_type' => 'post',
            ], $this->block['fields'] ?? get_fields() ?: []);

            $data['posts'] = get_posts($this->query($data['fields']));

            return $data;
        }

        protected function query($data): array
        {
            $query = [
                'posts_per_page' => $data->posts_per_page,
                'orderby' => implode(' ', $data->order_by),
                'order' => $data->order,
                'post_type' => $data->post_type,
                'post_status' => 'publish',
            ];

            return $query;
        }

        /**
        * {@inhertiDoc}
        */
        public function render(View $view): string
        {
            if (empty($view->posts)) {
                if ($this->isPreview()) {
                    return '<div class="acf-block-placeholder">' . __('No results found...') . '</div>';
                }
                return '';
            }

            return parent::render($view);
        }
    }
    ```

3. Add the block to `config/block.php`

    ```php
    'blocks' => [
        App\Blocks\ContentListing\ContentListing::class,
    ],
    ```

4. Create your default template: `views/content-listing.blade.php`

    ```blade
    <div class="{{ $classes }} flex">
      @foreach ($posts as $post)
        <div class="w-1/3">
          <h3>{{ get_the_title($post) }}</h3>

          {{ get_the_excerpt($post) }}
        </div>
      @endforeach
    </div>
    ```

5. Create your style variation template: `views/content-listing-<style>.blade.php`

    ```blade
    <div class="{{ $classes }}">
      @foreach ($posts as $post)
        <details>
          <summary>{{ get_the_title($post) }}</summary>

          {{ get_the_excerpt($post) }}
        </details>
      @endforeach
    </div>
    ```

### Additional features

#### Manual rendering outside of block editor.

If you want to render this block manually from a template rather than through the editor you can as long as you have enabled the in directive in `config/blocks.php`

```
@acfblock('acf/content-listing', [
  'post_type' => 'page',
  'className' => 'is-style-accordion',
  'align' => 'center',
])
```

If you want to include the template, each block has a registered view namespace.

```
@include('ContentListing::content-listing', [
  'classes' => 'acf-content-listing',
  'posts' => get_posts(),
])
```

#### Re-using templates and blocks

What if you wanted a block for _Handpicked content listing_ that uses an ACF relationship field to retrieve posts. The template would actually stay the same so let's add a block that extends the previous block and inherits templates and styles.

```php
<?php

namespace App\Blocks\FeaturedListing;

use App\Blocks\ContentListing\ContentListing;
use Genero\Sage\AcfBlocks\Facades\AcfBlock;

class FeaturedListing extends ContentListing
{

    /**
     * Block registration settings.
     *
     * @link https://www.advancedcustomfields.com/resources/acf_register_block_type/
     */
    public static function register(): array
    {
        $register = parent::register();
        $register['name'] = 'featured-listing';
        $register['title'] = 'Featured listing';
        $register['description'] = 'A block listing featured content picked manually';

        // The enqueues need to be updated to point to the correct block as they're
        // set based on the called class.
        $register['enqueue_style'] = AcfBlock::get(ContentListing::class . '.settings.enqueue_style');
        $register['enqueue_script'] = AcfBlock::get(ContentListing::class . '.settings.enqueue_script');

        return $register;
    }

    /**
     * Data to be passed to the rendered block.
     */
    public function with($data, $view)
    {
        $data['fields'] = (object) array_merge([
            'posts' => [],
        ], $this->block['fields'] ?? get_fields() ?: []);

        $data['posts'] = $data['fields']->posts ?: [];

        return $data;
    }

    /**
     * Data to be passed to the rendered block.
     */
    public function override($data, $view)
    {
        return [
            'classes' => $data['classes'] . ' acf-content-listing',
        ];
    }

    /**
     * {@inhertiDoc}
     */
    public function views(): array
    {
        return [
            "ContentListing::content-listing-{$this->style}",
            "ContentListing::content-listing",
        ];
    }
}
```
