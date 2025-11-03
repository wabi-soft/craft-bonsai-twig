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
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['nestByElementType'], 'boolean'],
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
