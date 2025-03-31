<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Detectors;

/**
 * Interface for all plural detectors which detects from translation string whether it should be pluralized in
 * Gettext output.
 */
interface PluralDetector
{
    /**
     * Returns true iff given message contain "numerical" placeholder, false otherwise.
     *
     * @param string $message
     * @return bool
     */
    public function hasPluralPlaceholder(string $message): bool;
}
