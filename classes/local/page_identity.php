<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_quicknote\local;

use invalid_parameter_exception;

/**
 * Creates stable, installation-local identities for Moodle pages.
 *
 * @package     local_quicknote
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class page_identity {
    /** Query arguments that must not split one logical page into several note pages. */
    private const VOLATILE_QUERY_KEYS = ['sesskey', '_qf__'];

    /**
     * Canonicalise a URL and make sure it belongs to this Moodle installation.
     *
     * The identity intentionally excludes scheme and host so notes survive a domain
     * migration, but only same-installation URLs are accepted at write time.
     *
     * @param string $url Absolute page URL.
     * @return string Path and stable query string.
     */
    public static function canonicalise(string $url): string {
        global $CFG;

        $urlparts = parse_url($url);
        $baseparts = parse_url($CFG->wwwroot);
        if ($urlparts === false || $baseparts === false || empty($urlparts['host']) || empty($baseparts['host'])) {
            throw new invalid_parameter_exception('A valid Moodle page URL is required.');
        }

        $urlport = $urlparts['port'] ?? self::default_port($urlparts['scheme'] ?? '');
        $baseport = $baseparts['port'] ?? self::default_port($baseparts['scheme'] ?? '');
        if (strtolower($urlparts['host']) !== strtolower($baseparts['host']) || $urlport !== $baseport) {
            throw new invalid_parameter_exception('QuickNote only accepts URLs from this Moodle installation.');
        }

        $path = $urlparts['path'] ?? '/';
        $query = [];
        if (!empty($urlparts['query'])) {
            parse_str($urlparts['query'], $query);
            foreach (self::VOLATILE_QUERY_KEYS as $key) {
                unset($query[$key]);
            }
            self::sort_recursive($query);
        }

        return $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }

    /**
     * Hash a canonical page identity for indexed database lookup.
     *
     * @param string $url Absolute Moodle URL.
     * @return string SHA-256 hash.
     */
    public static function hash(string $url): string {
        return hash('sha256', self::canonicalise($url));
    }

    /**
     * Hash a legacy saved URL without requiring its old host to match the current host.
     *
     * This is only for the upgrade migration; live writes must always use hash().
     *
     * @param string $url Previously stored absolute URL.
     * @return string
     */
    public static function legacy_hash(string $url): string {
        $parts = parse_url($url);
        if ($parts === false) {
            return hash('sha256', $url);
        }

        $path = $parts['path'] ?? '/';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (self::VOLATILE_QUERY_KEYS as $key) {
                unset($query[$key]);
            }
            self::sort_recursive($query);
        }
        $canonical = $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
        return hash('sha256', $canonical);
    }

    /**
     * Return a safe absolute same-installation URL without volatile query arguments.
     *
     * @param string $url Absolute Moodle URL.
     * @param bool $preservefragment Whether to retain a text-fragment or anchor.
     * @return string
     */
    public static function sanitise(string $url, bool $preservefragment = false): string {
        global $CFG;

        $baseparts = parse_url($CFG->wwwroot);
        $urlparts = parse_url($url);
        $origin = $baseparts['scheme'] . '://' . $baseparts['host'];
        if (isset($baseparts['port'])) {
            $origin .= ':' . $baseparts['port'];
        }

        $safeurl = $origin . self::canonicalise($url);
        if ($preservefragment && $urlparts !== false && !empty($urlparts['fragment'])) {
            $safeurl .= '#' . $urlparts['fragment'];
        }
        return $safeurl;
    }

    /**
     * Determine the default port for a URL scheme.
     *
     * @param string $scheme URL scheme.
     * @return int|null
     */
    private static function default_port(string $scheme): ?int {
        if (strtolower($scheme) === 'https') {
            return 443;
        }
        if (strtolower($scheme) === 'http') {
            return 80;
        }
        return null;
    }

    /**
     * Sort nested query data deterministically.
     *
     * @param array $values Query values.
     */
    private static function sort_recursive(array &$values): void {
        ksort($values);
        foreach ($values as &$value) {
            if (is_array($value)) {
                self::sort_recursive($value);
            }
        }
    }
}
