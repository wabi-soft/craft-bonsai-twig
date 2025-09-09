<?php

namespace wabisoft\bonsaitwig\tests\Fixtures;

/**
 * Fixture factory for creating test template content and paths.
 *
 * Provides realistic template content and path structures for testing
 * template resolution and rendering scenarios.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Fixtures
 * @since 6.4.0
 */
class TemplateFixtures
{
    /**
     * Creates a basic entry template content.
     *
     * @return string
     */
    public static function createEntryTemplate(): string
    {
        return <<<'TWIG'
<article class="entry">
    <header>
        <h1>{{ entry.title }}</h1>
        <time datetime="{{ entry.postDate|date('c') }}">
            {{ entry.postDate|date('F j, Y') }}
        </time>
    </header>
    
    <div class="content">
        {{ entry.body }}
    </div>
    
    {% if entry.categories|length %}
    <footer>
        <div class="categories">
            {% for category in entry.categories %}
                <span class="category">{{ category.title }}</span>
            {% endfor %}
        </div>
    </footer>
    {% endif %}
</article>
TWIG;
    }

    /**
     * Creates a category template content.
     *
     * @return string
     */
    public static function createCategoryTemplate(): string
    {
        return <<<'TWIG'
<div class="category">
    <header>
        <h2>{{ category.title }}</h2>
        {% if category.description %}
            <p class="description">{{ category.description }}</p>
        {% endif %}
    </header>
    
    {% set entries = craft.entries.relatedTo(category).limit(10) %}
    {% if entries|length %}
    <div class="entries">
        {% for entry in entries %}
            <article class="entry-summary">
                <h3><a href="{{ entry.url }}">{{ entry.title }}</a></h3>
                <p>{{ entry.summary ?? entry.body|striptags|truncate(150) }}</p>
            </article>
        {% endfor %}
    </div>
    {% endif %}
</div>
TWIG;
    }

    /**
     * Creates a matrix block template content.
     *
     * @return string
     */
    public static function createMatrixTemplate(): string
    {
        return <<<'TWIG'
<div class="matrix-block matrix-{{ block.type.handle }}">
    {% switch block.type.handle %}
        {% case 'textBlock' %}
            <div class="text-content">
                {{ block.content }}
            </div>
        
        {% case 'imageBlock' %}
            {% if block.image|length %}
                <figure class="image-block">
                    <img src="{{ block.image.one().url }}" alt="{{ block.image.one().alt }}">
                    {% if block.caption %}
                        <figcaption>{{ block.caption }}</figcaption>
                    {% endif %}
                </figure>
            {% endif %}
        
        {% case 'quoteBlock' %}
            <blockquote class="quote-block">
                <p>{{ block.quote }}</p>
                {% if block.author %}
                    <cite>{{ block.author }}</cite>
                {% endif %}
            </blockquote %}
        
        {% default %}
            <div class="unknown-block">
                <p>Unknown block type: {{ block.type.handle }}</p>
            </div>
    {% endswitch %}
</div>
TWIG;
    }

    /**
     * Creates a debug template content.
     *
     * @return string
     */
    public static function createDebugTemplate(): string
    {
        return <<<'TWIG'
<div class="bonsai-debug" style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;">
    <h4 style="margin: 0 0 10px 0; color: #333;">Bonsai Twig Debug Info</h4>
    
    <div class="debug-section">
        <strong>Template Type:</strong> {{ debugInfo.type.value }}<br>
        <strong>Directory:</strong> {{ debugInfo.directory }}<br>
        <strong>Current Template:</strong> {{ debugInfo.currentTemplate }}<br>
        <strong>Resolution Time:</strong> {{ (debugInfo.resolutionTime * 1000)|number_format(2) }}ms
    </div>
    
    {% if debugInfo.templates|length %}
    <div class="debug-section" style="margin-top: 10px;">
        <strong>Template Hierarchy:</strong>
        <ol style="margin: 5px 0; padding-left: 20px;">
            {% for template in debugInfo.templates %}
                <li style="margin: 2px 0;">
                    {% if template == debugInfo.currentTemplate %}
                        <strong style="color: green;">{{ template }}</strong> ✓
                    {% else %}
                        <span style="color: #666;">{{ template }}</span>
                    {% endif %}
                </li>
            {% endfor %}
        </ol>
    </div>
    {% endif %}
    
    {% if craft.app.config.general.devMode %}
    <div class="debug-section" style="margin-top: 10px; font-size: 11px; color: #666;">
        <strong>Element Info:</strong><br>
        ID: {{ element.id ?? 'N/A' }}<br>
        Type: {{ className(element) }}<br>
        {% if element.section is defined %}Section: {{ element.section.handle ?? 'N/A' }}<br>{% endif %}
        {% if element.type is defined %}Entry Type: {{ element.type.handle ?? 'N/A' }}<br>{% endif %}
        {% if element.group is defined %}Group: {{ element.group.handle ?? 'N/A' }}<br>{% endif %}
    </div>
    {% endif %}
</div>
TWIG;
    }

    /**
     * Creates template paths for entry hierarchy testing.
     *
     * @return array<string>
     */
    public static function createEntryTemplatePaths(): array
    {
        return [
            'entry/blog/article/my-awesome-post',
            'entry/blog/article/_entry',
            'entry/blog/_entry',
            'entry/_entry'
        ];
    }

    /**
     * Creates template paths for category hierarchy testing.
     *
     * @return array<string>
     */
    public static function createCategoryTemplatePaths(): array
    {
        return [
            'category/topics/technology',
            'category/topics/_category',
            'category/_category'
        ];
    }

    /**
     * Creates template paths for matrix block hierarchy testing.
     *
     * @return array<string>
     */
    public static function createMatrixTemplatePaths(): array
    {
        return [
            'matrix/pages/page/textBlock/highlighted',
            'matrix/pages/textBlock/highlighted',
            'matrix/textBlock/highlighted',
            'matrix/pages/page/textBlock',
            'matrix/pages/textBlock',
            'matrix/textBlock',
            'matrix/_matrix'
        ];
    }

    /**
     * Creates multi-site template paths for testing.
     *
     * @return array<string>
     */
    public static function createMultiSiteTemplatePaths(): array
    {
        return [
            'entry/fr/blog/article/_entry',
            'entry/blog/article/_entry',
            'entry/blog/_entry',
            'entry/_entry'
        ];
    }

    /**
     * Creates item template paths for testing.
     *
     * @return array<string>
     */
    public static function createItemTemplatePaths(): array
    {
        return [
            'item/products/product/premium-headphones',
            'item/products/product/_item',
            'item/products/_item',
            'item/_item'
        ];
    }

    /**
     * Creates expected HTML output for entry templates.
     *
     * @return string
     */
    public static function createExpectedEntryOutput(): string
    {
        return '<article class="entry"><h1>Understanding PHP 8.2 Features</h1><div class="content">Content about PHP 8.2...</div></article>';
    }

    /**
     * Creates expected HTML output for category templates.
     *
     * @return string
     */
    public static function createExpectedCategoryOutput(): string
    {
        return '<div class="category"><h2>Technology</h2><div class="entries">Related entries...</div></div>';
    }

    /**
     * Creates expected HTML output for matrix templates.
     *
     * @return string
     */
    public static function createExpectedMatrixOutput(): string
    {
        return '<div class="matrix-block matrix-textBlock"><div class="text-content">Block content here...</div></div>';
    }

    /**
     * Creates expected HTML output with debug information.
     *
     * @return string
     */
    public static function createExpectedDebugOutput(): string
    {
        $template = self::createExpectedEntryOutput();
        $debug = '<div class="bonsai-debug">Debug info: entry/blog/article/_entry (2.5ms)</div>';
        
        return $template . $debug;
    }

    /**
     * Creates cache keys for testing cache functionality.
     *
     * @return array<string, string>
     */
    public static function createCacheKeys(): array
    {
        return [
            'entry_1_blog_article' => 'bonsai_twig_entry_1_blog_article_default',
            'category_1_topics' => 'bonsai_twig_category_1_topics_default',
            'matrix_1_textBlock' => 'bonsai_twig_matrix_1_textBlock_default',
            'item_1_products' => 'bonsai_twig_item_1_products_default'
        ];
    }

    /**
     * Creates performance timing data for testing.
     *
     * @return array<string, float>
     */
    public static function createPerformanceData(): array
    {
        return [
            'cache_hit' => 0.0005,      // 0.5ms
            'cache_miss' => 0.008,      // 8ms
            'template_render' => 0.003,  // 3ms
            'path_generation' => 0.0001, // 0.1ms
            'validation' => 0.0002      // 0.2ms
        ];
    }

    /**
     * Creates error scenarios for testing exception handling.
     *
     * @return array<string, array>
     */
    public static function createErrorScenarios(): array
    {
        return [
            'template_not_found' => [
                'paths' => ['entry/nonexistent/_entry'],
                'exception' => 'TemplateNotFoundException',
                'message' => 'No template found for entry'
            ],
            'invalid_element' => [
                'element' => 'string_instead_of_element',
                'exception' => 'InvalidElementException',
                'message' => 'Expected Entry element'
            ],
            'path_traversal' => [
                'path' => '../../../etc/passwd',
                'sanitized' => 'etc/passwd',
                'message' => 'Path traversal attempt blocked'
            ]
        ];
    }
}