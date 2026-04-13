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
        $path = SecurityUtils::sanitizeTemplatePath(
            $this->paths[$type] ?? TemplateType::fromString($type)->getDefaultPath()
        );
        return $path !== '' ? $path : TemplateType::fromString($type)->getDefaultPath();
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
                    if (!is_string($v) || SecurityUtils::sanitizeTemplatePath($v) === '') {
                        $this->addError($attribute, "Invalid path for key '$k'. Value must be a non-empty string after sanitization.");
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
        return [];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return [];
    }
}
