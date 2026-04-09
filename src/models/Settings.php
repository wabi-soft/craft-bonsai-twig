<?php

namespace wabisoft\bonsaitwig\models;

use craft\base\Model;
use wabisoft\bonsaitwig\enums\Strategy;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\utilities\SecurityUtils;

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
     * @var string Template resolution strategy: 'section' (default) or 'type' (type-first).
     * @since 8.0.0
     */
    public string $strategy = 'section';

    /**
     * @var bool Whether to nest item template paths by element type when no explicit mapping is provided
     *           This acts as a fallback when itemsTemplateElementPaths is false.
     */
    public bool $nestByElementType = false;

    // Performance monitoring and error reporting settings removed in simplification

    /**
     * @var array<string,string>|false Mapping for Item templates to nest paths by element kind.
     * Example: {"entry": "entry", "category": "category"}. Set to false to disable.
     */
    public array|false $itemsTemplateElementPaths = false;

    /**
     * @var array<string,string> Override base paths for each element type.
     * Keys are element types (entry, item, category, matrix, asset, product).
     * Values are the base path to use instead of the default.
     * @since 9.0.0
     */
    public array $paths = [];

    /**
     * Returns the resolved base path for a given element type.
     *
     * Resolution order: paths config map > TemplateType default.
     * Per-template `path` param is handled by individual loaders before calling this.
     *
     * @since 9.0.0
     */
    public function getPathForType(string $type): string
    {
        $path = $this->paths[$type] ?? TemplateType::fromString($type)->getDefaultPath();
        return SecurityUtils::sanitizeTemplatePath($path);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['strategy'], 'in', 'range' => array_column(Strategy::cases(), 'value')],
            [['nestByElementType'], 'boolean'],
            [['paths'], function($attribute): void {
                $validKeys = array_column(TemplateType::cases(), 'value');
                foreach ($this->$attribute as $k => $v) {
                    if (!is_string($k) || !in_array($k, $validKeys, true)) {
                        $this->addError($attribute, "Invalid key '$k'. Must be one of: " . implode(', ', $validKeys));
                        break;
                    }
                    if (!is_string($v)) {
                        $this->addError($attribute, 'Values must be strings.');
                        break;
                    }
                }
            }],
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

            'itemsTemplateElementPaths' => 'Optional mapping used by the Item loader to nest paths by element type. Example: {"entry": "entry", "category": "category"}. Set to false to bypass. This replaces the older nestByElementType toggle.',
            'nestByElementType' => 'When enabled, item templates will be nested under item/entry or item/category automatically. Only used when itemsTemplateElementPaths is false.',
        ];
    }
}
