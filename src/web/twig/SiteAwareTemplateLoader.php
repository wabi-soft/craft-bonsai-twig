<?php

namespace wabisoft\bonsaitwig\web\twig;

use Craft;
use Twig\Loader\LoaderInterface;
use Twig\Source;
use wabisoft\bonsaitwig\BonsaiTwig;
use wabisoft\bonsaitwig\debug\ResolutionCollector;
use wabisoft\bonsaitwig\enums\TemplateType;

class SiteAwareTemplateLoader implements LoaderInterface
{
    private LoaderInterface $inner;

    /** @var list<string> */
    private array $bonsaiPrefixes;

    /** @var array<string, true> */
    private array $siteHandlePrefixes;

    /** @var array<string, string> */
    private array $resolved = [];

    private ?string $currentSiteHandle;
    private string $primarySiteHandle;
    private bool $devMode;

    public function __construct(LoaderInterface $inner)
    {
        $this->inner = $inner;
        $this->devMode = Craft::$app->getConfig()->general->devMode;
        $this->bonsaiPrefixes = $this->buildPrefixList();
        $this->siteHandlePrefixes = $this->buildSiteHandlePrefixMap();
        $this->primarySiteHandle = Craft::$app->getSites()->getPrimarySite()->handle;
        $this->currentSiteHandle = $this->resolveCurrentSiteHandle();
    }

    public function exists(string $name): bool
    {
        if ($this->inner->exists($name)) {
            return true;
        }

        return $this->resolveWithCascade($name) !== null;
    }

    public function getSourceContext(string $name): Source
    {
        $resolved = $this->resolve($name);

        return $this->inner->getSourceContext($resolved);
    }

    public function getCacheKey(string $name): string
    {
        $resolved = $this->resolve($name);

        return $this->inner->getCacheKey($resolved);
    }

    public function isFresh(string $name, int $time): bool
    {
        $resolved = $this->resolve($name);

        return $this->inner->isFresh($resolved, $time);
    }

    private function resolve(string $name): string
    {
        if (array_key_exists($name, $this->resolved)) {
            return $this->resolved[$name];
        }

        if ($this->inner->exists($name)) {
            return $this->resolved[$name] = $name;
        }

        return $this->resolved[$name] = $this->resolveWithCascade($name) ?? $name;
    }

    private function resolveWithCascade(string $name): ?string
    {
        if (!$this->hasBonsaiPrefix($name) || $this->hasSiteHandlePrefix($name)) {
            return null;
        }

        $tried = [];

        if ($this->currentSiteHandle) {
            $candidate = $this->currentSiteHandle . '/' . $name;
            $tried[] = $candidate;
            if ($this->inner->exists($candidate)) {
                $this->logCascade($name, $tried, $candidate);
                return $candidate;
            }
        }

        if ($this->primarySiteHandle !== $this->currentSiteHandle) {
            $candidate = $this->primarySiteHandle . '/' . $name;
            $tried[] = $candidate;
            if ($this->inner->exists($candidate)) {
                $this->logCascade($name, $tried, $candidate);
                return $candidate;
            }
        }

        $this->logCascade($name, $tried, null);

        return null;
    }

    private function hasBonsaiPrefix(string $name): bool
    {
        foreach ($this->bonsaiPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function hasSiteHandlePrefix(string $name): bool
    {
        $slashPos = strpos($name, '/');
        if ($slashPos === false) {
            return false;
        }

        return isset($this->siteHandlePrefixes[substr($name, 0, $slashPos + 1)]);
    }

    /** @return list<string> */
    private function buildPrefixList(): array
    {
        $prefixes = [];
        /** @var \wabisoft\bonsaitwig\models\Settings $settings */
        $settings = BonsaiTwig::getInstance()->getSettings();

        foreach (TemplateType::cases() as $type) {
            $path = $settings->getPathForType($type->value);
            $prefixes[$path . '/'] = true;
            $default = $type->getDefaultPath();
            if ($default !== $path) {
                $prefixes[$default . '/'] = true;
            }
        }

        return array_keys($prefixes);
    }

    /** @return array<string, true> */
    private function buildSiteHandlePrefixMap(): array
    {
        $map = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $map[$site->handle . '/'] = true;
        }

        return $map;
    }

    private function resolveCurrentSiteHandle(): ?string
    {
        try {
            return Craft::$app->getSites()->getCurrentSite()->handle;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param list<string> $tried */
    private function logCascade(string $original, array $tried, ?string $resolved): void
    {
        if (!$this->devMode) {
            return;
        }

        if (ResolutionCollector::isActive()) {
            ResolutionCollector::log('cascade', 'site-cascade', $tried, $resolved, $resolved);
        }

        if ($resolved) {
            Craft::info("Bonsai resolved '$original' → '$resolved'", __METHOD__);
        } else {
            $paths = implode(', ', $tried);
            Craft::info("Bonsai cascade: no site override for '$original' (tried: $paths)", __METHOD__);
        }
    }
}
