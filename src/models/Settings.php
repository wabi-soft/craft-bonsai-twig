<?php

namespace wabisoft\bonsaitwig\models;

use craft\base\Model;

/**
 * Bonsai Twig plugin settings model.
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\models
 * @since 6.4.0
 */
class Settings extends Model
{
    /**
     * @var bool Whether to enable caching in development mode
     */
    public bool $cacheInDevMode = false;

    /**
     * @var bool Whether to enable performance monitoring
     */
    public bool $enablePerformanceMonitoring = true;

    /**
     * @var bool Whether to enable error reporting
     */
    public bool $enableErrorReporting = true;

    /**
     * @var int Cache duration in seconds for template resolution (default: 1 hour)
     */
    public int $templateCacheDuration = 3600;

    /**
     * @var int Cache duration in seconds for element properties (default: 30 minutes)
     */
    public int $elementCacheDuration = 1800;

    /**
     * @var int Cache duration in seconds for template existence checks (default: 2 hours)
     */
    public int $existenceCacheDuration = 7200;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['cacheInDevMode', 'enablePerformanceMonitoring', 'enableErrorReporting'], 'boolean'],
            [['templateCacheDuration', 'elementCacheDuration', 'existenceCacheDuration'], 'integer', 'min' => 0],
            [['templateCacheDuration', 'elementCacheDuration', 'existenceCacheDuration'], 'default', 'value' => 3600],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'cacheInDevMode' => 'Enable Caching in Development Mode',
            'enablePerformanceMonitoring' => 'Enable Performance Monitoring',
            'enableErrorReporting' => 'Enable Error Reporting',
            'templateCacheDuration' => 'Template Cache Duration (seconds)',
            'elementCacheDuration' => 'Element Cache Duration (seconds)',
            'existenceCacheDuration' => 'Template Existence Cache Duration (seconds)',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return [
            'cacheInDevMode' => 'When enabled, template caching will work even in development mode. Useful for testing cache behavior during development.',
            'enablePerformanceMonitoring' => 'Enables performance monitoring and timing statistics in debug mode.',
            'enableErrorReporting' => 'Enables comprehensive error reporting and debugging information.',
            'templateCacheDuration' => 'How long to cache template resolution results (in seconds). Set to 0 to disable caching.',
            'elementCacheDuration' => 'How long to cache element property values (in seconds). Set to 0 to disable caching.',
            'existenceCacheDuration' => 'How long to cache template existence check results (in seconds). Set to 0 to disable caching.',
        ];
    }
}