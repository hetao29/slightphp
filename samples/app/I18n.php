<?php

use SlightPHP\Language;

/** Sample application language selection; translations are loaded by the Language plugin. */
final class SampleI18n
{
    public const LANGUAGES = ['zh-cn' => '简体中文', 'zh-tw' => '繁體中文', 'en' => 'English'];

    public static function boot(): void
    {
        $locale = self::supported($_GET['lang'] ?? null)
            ?? self::supported($_COOKIE['slightphp-language'] ?? null)
            ?? self::browserLocale($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')
            ?? 'zh-cn';
        Language::setLanguageDir(__DIR__ . '/languages');
        // The setter rebuilds language candidates on the next tr().
        Language::setLocale($locale);
        if (self::supported($_GET['lang'] ?? null) !== null) {
            (new \SlightPHP\Response())->cookie('slightphp-language', $locale, time() + 31536000);
        }
        header('Content-Language: ' . self::htmlLang());
        header('Vary: Accept-Language, Cookie');
    }

    public static function locale(): string
    {
        return Language::getLocale() ?? 'zh-cn';
    }

    public static function htmlLang(): string
    {
        return ['zh-cn' => 'zh-CN', 'zh-tw' => 'zh-TW', 'en' => 'en'][self::locale()];
    }

    public static function t(string $key): string
    {
        return Language::tr($key);
    }

    public static function e(string $key): string
    {
        return htmlspecialchars(self::t($key), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Build a full URL with encoded query components; Tpl handles final attribute encoding. */
    public static function url(string $locale): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            $path = '/';
        }
        $path = preg_replace('~^/index\.php(?=/|$)~', '', $path) ?: '/';
        $query = $_GET;
        $query['lang'] = self::supported($locale) ?? self::locale();
        return $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /** Return raw messages; Tpl serializes the array for its application/json script block. */
    public static function clientMessages(): array
    {
        $messages = [];
        foreach (['auto', 'dark', 'light', 'theme_cycle', 'plugin_count', 'task_count', 'copied', 'copy_manually', 'confirm_delete', 'sample_request_failed'] as $key) {
            $messages[$key] = self::t($key);
        }
        return $messages;
    }

    private static function supported($value): ?string
    {
        if (!is_string($value)) return null;
        $locale = strtolower($value);
        return isset(self::LANGUAGES[$locale]) ? $locale : null;
    }

    /** Match Language's ordered Accept-Language handling, including exclusion of q=0. */
    private static function browserLocale($header): ?string
    {
        if (!is_string($header) || strlen($header) > 8192 || strpbrk($header, "\r\n\0") !== false) return null;
        foreach (array_slice(explode(',', $header), 0, 32) as $range) {
            if (!preg_match('/\A([a-z]{1,8}(?:-[a-z0-9]{1,8})*)(?:[ \t]*;[ \t]*q=(0(?:\.[0-9]{0,3})?|1(?:\.0{0,3})?))?\z/i', trim($range, " \t"), $match)) continue;
            if (strlen($match[1]) > 255 || (isset($match[2]) && (float) $match[2] === 0.0)) continue;
            $tag = strtolower($match[1]);
            if ($tag === 'en' || str_starts_with($tag, 'en-')) return 'en';
            if (preg_match('/\Azh-(?:hant(?:-|$)|tw(?:-|$)|hk(?:-|$)|mo(?:-|$))/', $tag)) return 'zh-tw';
            if ($tag === 'zh' || str_starts_with($tag, 'zh-')) return 'zh-cn';
        }
        return null;
    }
}
