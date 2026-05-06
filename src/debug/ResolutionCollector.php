<?php

namespace wabisoft\bonsaitwig\debug;

class ResolutionCollector
{
    private static bool $active = false;

    /** @var list<array{type: string, strategy: string, tried: list<string>, resolved: ?string, resolvedPath: ?string, elementId: ?int, elementType: ?string}> */
    private static array $log = [];

    private static int $droppedCount = 0;

    public const MAX_ENTRIES = 200;

    public static function setActive(bool $active): void
    {
        if ($active) {
            self::reset();
        }

        self::$active = $active;
    }

    public static function isActive(): bool
    {
        return self::$active;
    }

    /** @param list<string> $tried */
    public static function log(
        string $type,
        string $strategy,
        array $tried,
        ?string $resolved,
        ?string $resolvedPath,
        ?int $elementId = null,
        ?string $elementType = null,
    ): void {
        if (!self::$active) {
            return;
        }

        if (count(self::$log) >= self::MAX_ENTRIES) {
            self::$droppedCount++;
            return;
        }

        self::$log[] = [
            'type' => $type,
            'strategy' => $strategy,
            'tried' => $tried,
            'resolved' => $resolved,
            'resolvedPath' => $resolvedPath,
            'elementId' => $elementId,
            'elementType' => $elementType,
        ];
    }

    /** @return list<array{type: string, strategy: string, tried: list<string>, resolved: ?string, resolvedPath: ?string, elementId: ?int, elementType: ?string}> */
    public static function getLog(): array
    {
        return self::$log;
    }

    public static function getDroppedCount(): int
    {
        return self::$droppedCount;
    }

    public static function reset(): void
    {
        self::$log = [];
        self::$droppedCount = 0;
    }
}
