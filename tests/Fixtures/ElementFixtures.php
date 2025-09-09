<?php

namespace wabisoft\bonsaitwig\tests\Fixtures;

use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\MatrixBlock;
use craft\models\Section;
use craft\models\EntryType;
use craft\models\CategoryGroup;
use craft\models\Site;
use Mockery;
use Mockery\MockInterface;

/**
 * Fixture factory for creating test elements with realistic data.
 *
 * Provides pre-configured mock objects for common testing scenarios
 * with realistic Craft CMS element structures.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests\Fixtures
 * @since 6.4.0
 */
class ElementFixtures
{
    /**
     * Creates a blog entry fixture with realistic blog structure.
     *
     * @return MockInterface&Entry
     */
    public static function createBlogEntry(): MockInterface
    {
        $entry = Mockery::mock(Entry::class);
        
        $entry->shouldReceive('getId')->andReturn(1);
        $entry->shouldReceive('getTitle')->andReturn('Understanding PHP 8.2 Features');
        $entry->shouldReceive('getSlug')->andReturn('understanding-php-82-features');
        
        // Mock section
        $section = Mockery::mock(Section::class);
        $section->shouldReceive('getAttribute')->with('handle')->andReturn('blog');
        $entry->shouldReceive('getSection')->andReturn($section);
        
        // Mock entry type
        $entryType = Mockery::mock(EntryType::class);
        $entryType->shouldReceive('getAttribute')->with('handle')->andReturn('article');
        $entry->shouldReceive('getType')->andReturn($entryType);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn('default');
        $entry->shouldReceive('getSite')->andReturn($site);
        
        return $entry;
    }

    /**
     * Creates a product entry fixture for e-commerce scenarios.
     *
     * @return MockInterface&Entry
     */
    public static function createProductEntry(): MockInterface
    {
        $entry = Mockery::mock(Entry::class);
        
        $entry->shouldReceive('getId')->andReturn(2);
        $entry->shouldReceive('getTitle')->andReturn('Premium Wireless Headphones');
        $entry->shouldReceive('getSlug')->andReturn('premium-wireless-headphones');
        
        // Mock section
        $section = Mockery::mock(Section::class);
        $section->shouldReceive('getAttribute')->with('handle')->andReturn('products');
        $entry->shouldReceive('getSection')->andReturn($section);
        
        // Mock entry type
        $entryType = Mockery::mock(EntryType::class);
        $entryType->shouldReceive('getAttribute')->with('handle')->andReturn('product');
        $entry->shouldReceive('getType')->andReturn($entryType);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn('default');
        $entry->shouldReceive('getSite')->andReturn($site);
        
        return $entry;
    }

    /**
     * Creates a page entry fixture for static content.
     *
     * @return MockInterface&Entry
     */
    public static function createPageEntry(): MockInterface
    {
        $entry = Mockery::mock(Entry::class);
        
        $entry->shouldReceive('getId')->andReturn(3);
        $entry->shouldReceive('getTitle')->andReturn('About Us');
        $entry->shouldReceive('getSlug')->andReturn('about-us');
        
        // Mock section
        $section = Mockery::mock(Section::class);
        $section->shouldReceive('getAttribute')->with('handle')->andReturn('pages');
        $entry->shouldReceive('getSection')->andReturn($section);
        
        // Mock entry type
        $entryType = Mockery::mock(EntryType::class);
        $entryType->shouldReceive('getAttribute')->with('handle')->andReturn('page');
        $entry->shouldReceive('getType')->andReturn($entryType);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn('default');
        $entry->shouldReceive('getSite')->andReturn($site);
        
        return $entry;
    }

    /**
     * Creates a multi-site entry fixture.
     *
     * @param string $siteHandle The site handle
     * @return MockInterface&Entry
     */
    public static function createMultiSiteEntry(string $siteHandle = 'fr'): MockInterface
    {
        $entry = Mockery::mock(Entry::class);
        
        $entry->shouldReceive('getId')->andReturn(4);
        $entry->shouldReceive('getTitle')->andReturn('À Propos de Nous');
        $entry->shouldReceive('getSlug')->andReturn('a-propos-de-nous');
        
        // Mock section
        $section = Mockery::mock(Section::class);
        $section->shouldReceive('getAttribute')->with('handle')->andReturn('pages');
        $entry->shouldReceive('getSection')->andReturn($section);
        
        // Mock entry type
        $entryType = Mockery::mock(EntryType::class);
        $entryType->shouldReceive('getAttribute')->with('handle')->andReturn('page');
        $entry->shouldReceive('getType')->andReturn($entryType);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn($siteHandle);
        $entry->shouldReceive('getSite')->andReturn($site);
        
        return $entry;
    }

    /**
     * Creates a category fixture for taxonomy scenarios.
     *
     * @return MockInterface&Category
     */
    public static function createTopicCategory(): MockInterface
    {
        $category = Mockery::mock(Category::class);
        
        $category->shouldReceive('getId')->andReturn(1);
        $category->shouldReceive('getTitle')->andReturn('Technology');
        $category->shouldReceive('getSlug')->andReturn('technology');
        
        // Mock category group
        $group = Mockery::mock(CategoryGroup::class);
        $group->shouldReceive('getAttribute')->with('handle')->andReturn('topics');
        $category->shouldReceive('getGroup')->andReturn($group);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn('default');
        $category->shouldReceive('getSite')->andReturn($site);
        
        return $category;
    }

    /**
     * Creates a product category fixture.
     *
     * @return MockInterface&Category
     */
    public static function createProductCategory(): MockInterface
    {
        $category = Mockery::mock(Category::class);
        
        $category->shouldReceive('getId')->andReturn(2);
        $category->shouldReceive('getTitle')->andReturn('Electronics');
        $category->shouldReceive('getSlug')->andReturn('electronics');
        
        // Mock category group
        $group = Mockery::mock(CategoryGroup::class);
        $group->shouldReceive('getAttribute')->with('handle')->andReturn('productCategories');
        $category->shouldReceive('getGroup')->andReturn($group);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn('default');
        $category->shouldReceive('getSite')->andReturn($site);
        
        return $category;
    }

    /**
     * Creates a text matrix block fixture.
     *
     * @param MockInterface|null $owner The owner element
     * @return MockInterface&MatrixBlock
     */
    public static function createTextMatrixBlock(?MockInterface $owner = null): MockInterface
    {
        $block = Mockery::mock(MatrixBlock::class);
        
        $block->shouldReceive('getId')->andReturn(1);
        $block->shouldReceive('getAttribute')->with('typeHandle')->andReturn('textBlock');
        
        if ($owner) {
            $block->shouldReceive('getOwner')->andReturn($owner);
        } else {
            $block->shouldReceive('getOwner')->andReturn(self::createPageEntry());
        }
        
        return $block;
    }

    /**
     * Creates an image matrix block fixture.
     *
     * @param MockInterface|null $owner The owner element
     * @return MockInterface&MatrixBlock
     */
    public static function createImageMatrixBlock(?MockInterface $owner = null): MockInterface
    {
        $block = Mockery::mock(MatrixBlock::class);
        
        $block->shouldReceive('getId')->andReturn(2);
        $block->shouldReceive('getAttribute')->with('typeHandle')->andReturn('imageBlock');
        
        if ($owner) {
            $block->shouldReceive('getOwner')->andReturn($owner);
        } else {
            $block->shouldReceive('getOwner')->andReturn(self::createPageEntry());
        }
        
        return $block;
    }

    /**
     * Creates a complex matrix block fixture with nested structure.
     *
     * @param MockInterface|null $owner The owner element
     * @return MockInterface&MatrixBlock
     */
    public static function createComplexMatrixBlock(?MockInterface $owner = null): MockInterface
    {
        $block = Mockery::mock(MatrixBlock::class);
        
        $block->shouldReceive('getId')->andReturn(3);
        $block->shouldReceive('getAttribute')->with('typeHandle')->andReturn('complexBlock');
        
        if ($owner) {
            $block->shouldReceive('getOwner')->andReturn($owner);
        } else {
            $block->shouldReceive('getOwner')->andReturn(self::createBlogEntry());
        }
        
        return $block;
    }

    /**
     * Creates a collection of related entries for testing hierarchies.
     *
     * @return array<MockInterface&Entry>
     */
    public static function createEntryHierarchy(): array
    {
        $parentEntry = Mockery::mock(Entry::class);
        $parentEntry->shouldReceive('getId')->andReturn(10);
        $parentEntry->shouldReceive('getTitle')->andReturn('Parent Page');
        $parentEntry->shouldReceive('getSlug')->andReturn('parent-page');
        
        $section = Mockery::mock(Section::class);
        $section->shouldReceive('getAttribute')->with('handle')->andReturn('pages');
        $parentEntry->shouldReceive('getSection')->andReturn($section);
        
        $entryType = Mockery::mock(EntryType::class);
        $entryType->shouldReceive('getAttribute')->with('handle')->andReturn('page');
        $parentEntry->shouldReceive('getType')->andReturn($entryType);
        
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn('default');
        $parentEntry->shouldReceive('getSite')->andReturn($site);
        
        $childEntry = Mockery::mock(Entry::class);
        $childEntry->shouldReceive('getId')->andReturn(11);
        $childEntry->shouldReceive('getTitle')->andReturn('Child Page');
        $childEntry->shouldReceive('getSlug')->andReturn('child-page');
        $childEntry->shouldReceive('getSection')->andReturn($section);
        $childEntry->shouldReceive('getType')->andReturn($entryType);
        $childEntry->shouldReceive('getSite')->andReturn($site);
        
        return [$parentEntry, $childEntry];
    }

    /**
     * Creates template context variables for testing.
     *
     * @return array<string, mixed>
     */
    public static function createTemplateVariables(): array
    {
        return [
            'showAuthor' => true,
            'showDate' => true,
            'showCategories' => false,
            'customClass' => 'featured-content',
            'metadata' => [
                'created' => '2024-01-15',
                'updated' => '2024-01-20',
                'author' => 'John Doe'
            ],
            'settings' => [
                'displayMode' => 'full',
                'enableComments' => true,
                'socialSharing' => ['twitter', 'facebook', 'linkedin']
            ]
        ];
    }

    /**
     * Creates debug context variables for testing debug modes.
     *
     * @return array<string, mixed>
     */
    public static function createDebugVariables(): array
    {
        return [
            'debugMode' => 'full',
            'showTiming' => true,
            'showPaths' => true,
            'showCache' => true,
            'verboseOutput' => false
        ];
    }
}