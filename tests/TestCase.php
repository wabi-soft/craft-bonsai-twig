<?php

namespace wabisoft\bonsaitwig\tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Mockery;
use Mockery\MockInterface;
use craft\base\Element;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\MatrixBlock;
use craft\models\Section;
use craft\models\EntryType;
use craft\models\CategoryGroup;
use craft\models\Site;

/**
 * Base test case for Bonsai Twig plugin tests.
 *
 * Provides common functionality for all test classes including mock object
 * creation, test data generation, and assertion helpers.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\tests
 * @since 6.4.0
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Clean up Mockery after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Creates a mock Entry element with common properties.
     *
     * @param array<string, mixed> $properties Additional properties to set
     * @return MockInterface&Entry
     */
    protected function createMockEntry(array $properties = []): MockInterface
    {
        $entry = Mockery::mock(Entry::class);
        
        // Set default properties
        $entry->shouldReceive('getId')->andReturn($properties['id'] ?? 1);
        $entry->shouldReceive('getTitle')->andReturn($properties['title'] ?? 'Test Entry');
        $entry->shouldReceive('getSlug')->andReturn($properties['slug'] ?? 'test-entry');
        
        // Mock section
        $section = Mockery::mock(Section::class);
        $section->shouldReceive('getAttribute')->with('handle')->andReturn($properties['sectionHandle'] ?? 'testSection');
        $entry->shouldReceive('getSection')->andReturn($section);
        
        // Mock entry type
        $entryType = Mockery::mock(EntryType::class);
        $entryType->shouldReceive('getAttribute')->with('handle')->andReturn($properties['typeHandle'] ?? 'testType');
        $entry->shouldReceive('getType')->andReturn($entryType);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn($properties['siteHandle'] ?? 'default');
        $entry->shouldReceive('getSite')->andReturn($site);
        
        return $entry;
    }

    /**
     * Creates a mock Category element with common properties.
     *
     * @param array<string, mixed> $properties Additional properties to set
     * @return MockInterface&Category
     */
    protected function createMockCategory(array $properties = []): MockInterface
    {
        $category = Mockery::mock(Category::class);
        
        // Set default properties
        $category->shouldReceive('getId')->andReturn($properties['id'] ?? 1);
        $category->shouldReceive('getTitle')->andReturn($properties['title'] ?? 'Test Category');
        $category->shouldReceive('getSlug')->andReturn($properties['slug'] ?? 'test-category');
        
        // Mock category group
        $group = Mockery::mock(CategoryGroup::class);
        $group->shouldReceive('getAttribute')->with('handle')->andReturn($properties['groupHandle'] ?? 'testGroup');
        $category->shouldReceive('getGroup')->andReturn($group);
        
        // Mock site
        $site = Mockery::mock(Site::class);
        $site->shouldReceive('getAttribute')->with('handle')->andReturn($properties['siteHandle'] ?? 'default');
        $category->shouldReceive('getSite')->andReturn($site);
        
        return $category;
    }

    /**
     * Creates a mock MatrixBlock element with common properties.
     *
     * @param array<string, mixed> $properties Additional properties to set
     * @return MockInterface&MatrixBlock
     */
    protected function createMockMatrixBlock(array $properties = []): MockInterface
    {
        $block = Mockery::mock(MatrixBlock::class);
        
        // Set default properties
        $block->shouldReceive('getId')->andReturn($properties['id'] ?? 1);
        $block->shouldReceive('getAttribute')->with('typeHandle')->andReturn($properties['typeHandle'] ?? 'testBlock');
        
        // Mock owner element
        if (isset($properties['owner'])) {
            $block->shouldReceive('getOwner')->andReturn($properties['owner']);
        }
        
        return $block;
    }

    /**
     * Creates a mock Craft application for testing.
     *
     * @return MockInterface
     */
    protected function createMockCraftApp(): MockInterface
    {
        $app = Mockery::mock('craft\web\Application');
        
        // Mock view component
        $view = Mockery::mock('craft\web\View');
        $app->shouldReceive('getView')->andReturn($view);
        
        // Mock config component
        $config = Mockery::mock('craft\services\Config');
        $generalConfig = Mockery::mock('craft\config\GeneralConfig');
        $generalConfig->shouldReceive('getAttribute')->with('devMode')->andReturn(true);
        $config->shouldReceive('getGeneral')->andReturn($generalConfig);
        $app->shouldReceive('getConfig')->andReturn($config);
        
        // Mock cache component
        $cache = Mockery::mock('craft\cache\FileCache');
        $app->shouldReceive('getCache')->andReturn($cache);
        
        return $app;
    }

    /**
     * Asserts that a string contains valid HTML.
     *
     * @param string $html The HTML string to validate
     * @param string $message Optional assertion message
     * @return void
     */
    protected function assertValidHtml(string $html, string $message = ''): void
    {
        $this->assertNotEmpty($html, $message ?: 'HTML should not be empty');
        
        // Basic HTML validation - check for balanced tags
        $openTags = preg_match_all('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', $html, $openMatches);
        $closeTags = preg_match_all('/<\/([a-zA-Z][a-zA-Z0-9]*)\s*>/', $html, $closeMatches);
        
        if ($openTags > 0 || $closeTags > 0) {
            $this->assertGreaterThanOrEqual(
                $closeTags,
                $openTags,
                $message ?: 'HTML should have balanced tags'
            );
        }
    }

    /**
     * Asserts that an array contains expected template paths.
     *
     * @param array<string> $paths The array of template paths
     * @param array<string> $expectedPaths Expected paths to find
     * @param string $message Optional assertion message
     * @return void
     */
    protected function assertContainsTemplatePaths(array $paths, array $expectedPaths, string $message = ''): void
    {
        foreach ($expectedPaths as $expectedPath) {
            $this->assertContains(
                $expectedPath,
                $paths,
                $message ?: "Template paths should contain '{$expectedPath}'"
            );
        }
    }

    /**
     * Asserts that a performance measurement is within acceptable bounds.
     *
     * @param float $actualTime The measured time in seconds
     * @param float $maxTime Maximum acceptable time in seconds
     * @param string $message Optional assertion message
     * @return void
     */
    protected function assertPerformanceWithinBounds(float $actualTime, float $maxTime, string $message = ''): void
    {
        $this->assertLessThanOrEqual(
            $maxTime,
            $actualTime,
            $message ?: "Performance should be within {$maxTime}s, got {$actualTime}s"
        );
    }
}