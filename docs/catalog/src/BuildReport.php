<?php
declare(strict_types=1);

namespace BasecoatTheme\Docs;

/**
 * Result of a catalog build.
 */
final class BuildReport
{
    /**
     * @param list<string> $pages Generated pages, relative to the output directory.
     * @param list<string> $warnings Non-fatal problems (fatal with `--strict`).
     */
    public function __construct(
        public readonly array $pages,
        public readonly array $warnings,
    ) {
    }
}
