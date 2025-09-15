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
     * @var bool Whether to nest item template paths by element type when no explicit mapping is provided
     *           This acts as a fallback when itemsTemplateElementPaths is false.
     */
    public bool $nestByElementType = false;

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
     * @var array<string,string>|false Mapping for Item templates to nest paths by element kind.
     * Example: {"entry": "entry", "category": "category"}. Set to false to disable.
     */
    public array|false $itemsTemplateElementPaths = false;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['cacheInDevMode', 'enablePerformanceMonitoring', 'enableErrorReporting', 'nestByElementType'], 'boolean'],
            [['templateCacheDuration', 'elementCacheDuration', 'existenceCacheDuration'], 'integer', 'min' => 0],
            [['templateCacheDuration', 'elementCacheDuration', 'existenceCacheDuration'], 'default', 'value' => 3600],
            // itemsTemplateElementPaths can be false or an associative array of strings
            [['itemsTemplateElementPaths'], function($attribute): void {
                $value = $this->$attribute;
                if ($value === false) {
                    return; // allowed
                }
                if (!is_array($value)) {
                    $this->addError($attribute, 'Must be an associative array or false.');
                    return;
                }
                foreach ($value as $k => $v) {
                    if (!is_string($k) || !is_string($v)) {
                        $this->addError($attribute, 'Keys and values must be strings when provided as an array.');
                        break;
                    }
                }
            }],
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
            'itemsTemplateElementPaths' => 'Item Templates: Element Path Prefixes',
                        'nestByElementType' => 'Item Templates: Nest Paths by Element Type (fallback)',
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
            'itemsTemplateElementPaths' => 'Optional mapping used by the Item loader to nest paths by element type. Example: {"entry": "entry", "category": "category"}. Set to false to bypass. This replaces the older nestByElementType toggle.',
            'nestByElementType' => 'When enabled, item templates will be nested under item/entry or item/category automatically. Only used when itemsTemplateElementPaths is false.',
        ];
    }
}