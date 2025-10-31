<?php

namespace wabisoft\bonsaitwig\services;

use Craft;
use wabisoft\bonsaitwig\enums\TemplateType;
use wabisoft\bonsaitwig\valueobjects\DebugInfo;
use wabisoft\bonsaitwig\valueobjects\TemplateContext;
use yii\base\Component;

/**
 * Performance monitoring service for development mode.
 *
 * This service provides comprehensive performance monitoring capabilities
 * for template resolution, including timing measurements, memory usage tracking,
 * and performance metrics collection and display.
 *
 * Features:
 * - Template resolution timing with microsecond precision
 * - Memory usage tracking for development mode
 * - Performance metrics collection and aggregation
 * - Debug information formatting and display
 * - Performance bottleneck identification
 * - Cache hit/miss ratio tracking
 *
 * @author Wabisoft
 * @package wabisoft\bonsaitwig\services
 * @since 6.4.0
 */
class PerformanceMonitor extends Component
{
    /**
     * Active timing sessions indexed by session ID.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $timingSessions = [];

    /**
     * Performance metrics collected during execution.
     *
     * @var array<string, mixed>
     */
    private array $performanceMetrics = [];

    /**
     * Memory usage snapshots at different points.
     *
     * @var array<string, int>
     */
    private array $memorySnapshots = [];

    /**
     * Cache statistics for hit/miss tracking.
     *
     * @var array<string, int>
     */
    private array $cacheStats = [
        'hits' => 0,
        'misses' => 0,
        'total_requests' => 0,
    ];

    /**
     * Template resolution statistics.
     *
     * @var array<string, mixed>
     */
    private array $resolutionStats = [
        'total_resolutions' => 0,
        'successful_resolutions' => 0,
        'failed_resolutions' => 0,
        'average_resolution_time' => 0.0,
        'slowest_resolution' => 0.0,
        'fastest_resolution' => PHP_FLOAT_MAX,
    ];

    /**
     * Starts timing measurement for a specific operation.
     *
     * Creates a new timing session with microsecond precision timing
     * and memory usage tracking.
     *
     * @param string $sessionId Unique identifier for this timing session
     * @param string $operation Description of the operation being timed
     * @param array<string, mixed> $context Additional context about the operation
     * @return void
     */
    public function startTiming(string $sessionId, string $operation, array $context = []): void
    {
        // Only track performance in development mode
        if (!Craft::$app->getConfig()->general->devMode) {
            return;
        }

        $this->timingSessions[$sessionId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'start_peak_memory' => memory_get_peak_usage(true),
            'context' => $context,
            'checkpoints' => [],
        ];

        // Take initial memory snapshot
        $this->memorySnapshots[$sessionId . '_start'] = memory_get_usage(true);
    }

    /**
     * Adds a checkpoint to an active timing session.
     *
     * Allows tracking of intermediate timing points within a longer operation.
     *
     * @param string $sessionId Timing session identifier
     * @param string $checkpoint Name of the checkpoint
     * @param array<string, mixed> $data Additional data to store with checkpoint
     * @return void
     */
    public function addCheckpoint(string $sessionId, string $checkpoint, array $data = []): void
    {
        // Only track performance in development mode
        if (!Craft::$app->getConfig()->general->devMode) {
            return;
        }

        if (!isset($this->timingSessions[$sessionId])) {
            return;
        }

        $currentTime = microtime(true);
        $startTime = $this->timingSessions[$sessionId]['start_time'];

        $this->timingSessions[$sessionId]['checkpoints'][$checkpoint] = [
            'time' => $currentTime,
            'elapsed' => $currentTime - $startTime,
            'memory' => memory_get_usage(true),
            'data' => $data,
        ];

        // Take memory snapshot
        $this->memorySnapshots[$sessionId . '_' . $checkpoint] = memory_get_usage(true);
    }

    /**
     * Ends timing measurement and returns performance data.
     *
     * Completes the timing session and calculates final performance metrics.
     *
     * @param string $sessionId Timing session identifier
     * @return array<string, mixed>|null Performance data or null if session not found
     */
    public function endTiming(string $sessionId): ?array
    {
        // Only track performance in development mode
        if (!Craft::$app->getConfig()->general->devMode) {
            return null;
        }

        if (!isset($this->timingSessions[$sessionId])) {
            return null;
        }

        $session = $this->timingSessions[$sessionId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);

        $performanceData = [
            'operation' => $session['operation'],
            'total_time' => $endTime - $session['start_time'],
            'start_time' => $session['start_time'],
            'end_time' => $endTime,
            'memory_usage' => [
                'start' => $session['start_memory'],
                'end' => $endMemory,
                'peak' => $endPeakMemory,
                'delta' => $endMemory - $session['start_memory'],
                'peak_delta' => $endPeakMemory - $session['start_peak_memory'],
            ],
            'checkpoints' => $session['checkpoints'],
            'context' => $session['context'],
        ];

        // Update resolution statistics
        $this->updateResolutionStats($performanceData);

        // Store in performance metrics
        $this->performanceMetrics[$sessionId] = $performanceData;

        // Take final memory snapshot
        $this->memorySnapshots[$sessionId . '_end'] = $endMemory;

        // Clean up session
        unset($this->timingSessions[$sessionId]);

        return $performanceData;
    }

    /**
     * Records cache hit or miss for statistics.
     *
     * @param bool $hit True for cache hit, false for cache miss
     * @param string $cacheType Type of cache (template, element, existence)
     * @return void
     */
    public function recordCacheAccess(bool $hit, string $cacheType = 'general'): void
    {
        // Only track performance in development mode
        if (!Craft::$app->getConfig()->general->devMode) {
            return;
        }

        $this->cacheStats['total_requests']++;
        
        if ($hit) {
            $this->cacheStats['hits']++;
        } else {
            $this->cacheStats['misses']++;
        }

        // Track by cache type
        if (!isset($this->cacheStats['by_type'][$cacheType])) {
            $this->cacheStats['by_type'][$cacheType] = ['hits' => 0, 'misses' => 0];
        }

        if ($hit) {
            $this->cacheStats['by_type'][$cacheType]['hits']++;
        } else {
            $this->cacheStats['by_type'][$cacheType]['misses']++;
        }
    }

    /**
     * Records template resolution attempt.
     *
     * @param bool $successful Whether the resolution was successful
     * @param float $resolutionTime Time taken for resolution in seconds
     * @param int $pathsAttempted Number of template paths attempted
     * @return void
     */
    public function recordTemplateResolution(
        bool $successful,
        float $resolutionTime,
        int $pathsAttempted = 0,
    ): void {
        // Only track performance in development mode
        if (!Craft::$app->getConfig()->general->devMode) {
            return;
        }

        $this->resolutionStats['total_resolutions']++;
        
        if ($successful) {
            $this->resolutionStats['successful_resolutions']++;
        } else {
            $this->resolutionStats['failed_resolutions']++;
        }

        // Update timing statistics
        if ($resolutionTime > $this->resolutionStats['slowest_resolution']) {
            $this->resolutionStats['slowest_resolution'] = $resolutionTime;
        }

        if ($resolutionTime < $this->resolutionStats['fastest_resolution']) {
            $this->resolutionStats['fastest_resolution'] = $resolutionTime;
        }

        // Calculate running average
        $totalResolutions = $this->resolutionStats['total_resolutions'];
        $currentAverage = $this->resolutionStats['average_resolution_time'];
        $this->resolutionStats['average_resolution_time'] =
            (($currentAverage * ($totalResolutions - 1)) + $resolutionTime) / $totalResolutions;

        // Track paths attempted
        if (!isset($this->resolutionStats['paths_attempted'])) {
            $this->resolutionStats['paths_attempted'] = [];
        }
        $this->resolutionStats['paths_attempted'][] = $pathsAttempted;
    }

    /**
     * Gets current memory usage information.
     *
     * @return array<string, mixed> Memory usage statistics
     */
    public function getCurrentMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'current_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak' => memory_get_peak_usage(true),
            'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'snapshots' => array_map(
                fn(int $bytes): string => $this->formatBytes($bytes),
                $this->memorySnapshots
            ),
        ];
    }

    /**
     * Gets cache performance statistics.
     *
     * @return array<string, mixed> Cache statistics including hit rates
     */
    public function getCacheStatistics(): array
    {
        $totalRequests = $this->cacheStats['total_requests'];
        
        if ($totalRequests === 0) {
            return [
                'hit_rate' => 0.0,
                'miss_rate' => 0.0,
                'total_requests' => 0,
                'hits' => 0,
                'misses' => 0,
                'by_type' => [],
            ];
        }

        $hitRate = ($this->cacheStats['hits'] / $totalRequests) * 100;
        $missRate = ($this->cacheStats['misses'] / $totalRequests) * 100;

        $stats = [
            'hit_rate' => round($hitRate, 2),
            'miss_rate' => round($missRate, 2),
            'total_requests' => $totalRequests,
            'hits' => $this->cacheStats['hits'],
            'misses' => $this->cacheStats['misses'],
            'by_type' => [],
        ];

        // Calculate hit rates by cache type
        if (isset($this->cacheStats['by_type'])) {
            foreach ($this->cacheStats['by_type'] as $type => $typeStats) {
                $typeTotal = $typeStats['hits'] + $typeStats['misses'];
                if ($typeTotal > 0) {
                    $stats['by_type'][$type] = [
                        'hits' => $typeStats['hits'],
                        'misses' => $typeStats['misses'],
                        'total' => $typeTotal,
                        'hit_rate' => round(($typeStats['hits'] / $typeTotal) * 100, 2),
                    ];
                }
            }
        }

        return $stats;
    }

    /**
     * Gets template resolution performance statistics.
     *
     * @return array<string, mixed> Resolution performance data
     */
    public function getResolutionStatistics(): array
    {
        $stats = $this->resolutionStats;

        // Calculate success rate
        if ($stats['total_resolutions'] > 0) {
            $stats['success_rate'] = round(
                ($stats['successful_resolutions'] / $stats['total_resolutions']) * 100,
                2
            );
        } else {
            $stats['success_rate'] = 0.0;
        }

        // Format timing values
        $stats['average_resolution_time_formatted'] = $this->formatTime($stats['average_resolution_time']);
        $stats['slowest_resolution_formatted'] = $this->formatTime($stats['slowest_resolution']);
        $stats['fastest_resolution_formatted'] = $this->formatTime(
            $stats['fastest_resolution'] === PHP_FLOAT_MAX ? 0.0 : $stats['fastest_resolution']
        );

        // Calculate average paths attempted
        if (isset($stats['paths_attempted']) && !empty($stats['paths_attempted'])) {
            $stats['average_paths_attempted'] = round(
                array_sum($stats['paths_attempted']) / count($stats['paths_attempted']),
                1
            );
        } else {
            $stats['average_paths_attempted'] = 0.0;
        }

        return $stats;
    }

    /**
     * Creates enhanced debug information with performance metrics.
     *
     * @param TemplateContext $context Template resolution context
     * @param array<string> $attemptedPaths Paths that were attempted
     * @param string|null $resolvedPath Successfully resolved path
     * @param array<string, mixed> $performanceData Performance timing data
     * @return DebugInfo Enhanced debug information object
     */
    public function createEnhancedDebugInfo(
        TemplateContext $context,
        array $attemptedPaths,
        ?string $resolvedPath,
        array $performanceData = [],
    ): DebugInfo {
        $templateType = TemplateType::ENTRY; // TODO: derive from $context

        // Create base debug info
        $currentTemplate = $resolvedPath ?? ($attemptedPaths[0] ?? $context->getSanitizedPath());
        $debugInfo = new DebugInfo(
            directory: $context->path,
            templates: $attemptedPaths,
            currentTemplate: $currentTemplate,
            type: $templateType,
            resolutionTime: $performanceData['total_time'] ?? 0.0
        );

        return $debugInfo;
    }

    /**
     * Gets comprehensive performance report for debug display.
     *
     * @return array<string, mixed> Complete performance report
     */
    public function getPerformanceReport(): array
    {
        return [
            'memory' => $this->getCurrentMemoryUsage(),
            'cache' => $this->getCacheStatistics(),
            'resolution' => $this->getResolutionStatistics(),
            'active_sessions' => count($this->timingSessions),
            'completed_operations' => count($this->performanceMetrics),
            'recent_operations' => array_slice($this->performanceMetrics, -5, 5, true),
        ];
    }

    /**
     * Resets all performance metrics.
     *
     * Clears all collected performance data. Useful for testing or
     * when starting fresh measurements.
     *
     * @return void
     */
    public function resetMetrics(): void
    {
        $this->timingSessions = [];
        $this->performanceMetrics = [];
        $this->memorySnapshots = [];
        $this->cacheStats = [
            'hits' => 0,
            'misses' => 0,
            'total_requests' => 0,
        ];
        $this->resolutionStats = [
            'total_resolutions' => 0,
            'successful_resolutions' => 0,
            'failed_resolutions' => 0,
            'average_resolution_time' => 0.0,
            'slowest_resolution' => 0.0,
            'fastest_resolution' => PHP_FLOAT_MAX,
        ];
    }

    /**
     * Updates resolution statistics with new performance data.
     *
     * @param array<string, mixed> $performanceData Performance data from completed operation
     * @return void
     */
    private function updateResolutionStats(array $performanceData): void
    {
        $resolutionTime = $performanceData['total_time'];
        $successful = isset($performanceData['context']['resolved']) && $performanceData['context']['resolved'];
        $pathsAttempted = count($performanceData['context']['attempted_paths'] ?? []);

        $this->recordTemplateResolution($successful, $resolutionTime, $pathsAttempted);
    }

    /**
     * Formats bytes into human-readable format.
     *
     * @param int $bytes Number of bytes
     * @return string Formatted string (e.g., "1.5 MB")
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Formats time duration into human-readable format.
     *
     * @param float $seconds Time in seconds
     * @return string Formatted string (e.g., "1.23ms")
     */
    private function formatTime(float $seconds): string
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000, 2) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        } else {
            return round($seconds, 3) . 's';
        }
    }
}
