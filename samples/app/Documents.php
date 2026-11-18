<?php

/** Read-only browser for the repository documentation and generated API reference. */
final class SampleDocuments
{
    private static ?array $files = null;
    private array $headings = [];
    private int $codeIndex = 0;

    public static function files(): array
    {
        if (self::$files !== null) return self::$files;
        $files = [];
        foreach (glob(dirname(__DIR__, 2) . '/docs/*.md') as $path) {
            $source = file_get_contents($path);
            preg_match('/\A# (.+)/', $source, $match);
            $files[basename($path)] = $match[1] ?? basename($path);
        }
        $files = ['README.md' => $files['README.md']] + $files;
        $files['index.html'] = 'API Reference';
        $files['CHANGELOG.md'] = 'ChangeLog';
        return self::$files = $files;
    }

    /** Names are selected from the known documentation list, never arbitrary paths. */
    public static function page(string $name): ?array
    {
        $files = self::files();
        if (!isset($files[$name])) return null;
        $path = $name === 'CHANGELOG.md'
            ? dirname(__DIR__, 2) . '/CHANGELOG.md'
            : dirname(__DIR__, 2) . '/docs/' . $name;
        $source = file_get_contents($path);
        if ($source === false) throw new RuntimeException('Cannot read documentation');
        $renderer = new self();
        if ($name === 'index.html') {
            // This is the repository-owned generated API, with no external assets.
            preg_match('~<body>(.*)</body>~s', $source, $match);
            $html = preg_replace_callback('/href="([^"]*)"/', static function (array $link): string {
                return 'href="' . self::escape(self::link(html_entity_decode($link[1], ENT_QUOTES, 'UTF-8'))) . '"';
            }, $match[1] ?? '');
        } else {
            $html = $renderer->markdown($source);
        }
        return ['name' => $name, 'title' => $files[$name], 'html' => $html];
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Resolve local references into the documentation browser. */
    private static function link(string $url): string
    {
        if (str_starts_with($url, '#') || preg_match('~\Ahttps?://~i', $url)) return $url;
        [$path, $anchor] = array_pad(explode('#', $url, 2), 2, null);
        // Root-level ChangeLog links use docs/...; keep them inside the document browser.
        if (str_starts_with($path, 'docs/')) $path = substr($path, 5);
        if ($path === '../CHANGELOG.md') return '/site/main/docs?doc=CHANGELOG.md' . ($anchor !== null ? '#' . $anchor : '');
        if (isset(self::files()[$path])) {
            return '/site/main/docs?doc=' . rawurlencode($path) . ($anchor !== null ? '#' . $anchor : '');
        }
        if (str_starts_with($path, '../')) {
            return 'https://github.com/hetao29/slightphp/blob/master/' . substr($url, 3);
        }
        // Unknown schemes and paths are displayed as text instead of made executable.
        return '#';
    }

    private static function inline(string $text): string
    {
        $parts = preg_split('/(`[^`]+`|\[[^\]]+\]\([^\s)]+\))/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        return implode('', array_map(static function (string $part): string {
            if (str_starts_with($part, '`') && str_ends_with($part, '`')) {
                return '<code>' . self::escape(substr($part, 1, -1)) . '</code>';
            }
            if (preg_match('/\A\[([^\]]+)\]\(([^\s)]+)\)\z/', $part, $match)) {
                return '<a href="' . self::escape(self::link($match[2])) . '">' . self::escape($match[1]) . '</a>';
            }
            return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', self::escape($part));
        }, $parts));
    }

    private function heading(string $text): string
    {
        $id = strtolower(preg_replace('/[^\p{L}\p{N}_ -]/u', '', str_replace('`', '', $text)));
        $id = str_replace(' ', '-', $id);
        $number = $this->headings[$id] ?? 0;
        $this->headings[$id] = $number + 1;
        return $number ? $id . '-' . $number : $id;
    }

    private static function cells(string $line): array
    {
        // Pipes inside inline code (e.g. union types) do not delimit table cells.
        return preg_split('/\|(?=(?:[^`]*`[^`]*`)*[^`]*$)/', trim(trim($line), '|'));
    }

    /** Render the Markdown constructs used by docs, escaping text and fenced code. */
    private function markdown(string $source): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $source));
        $html = '';
        $paragraph = [];
        $list = null;
        $flush = static function () use (&$html, &$paragraph, &$list): void {
            if ($paragraph) $html .= '<p>' . self::inline(implode(' ', $paragraph)) . '</p>';
            $paragraph = [];
            if ($list !== null) $html .= '</' . $list . '>';
            $list = null;
        };
        for ($i = 0, $count = count($lines); $i < $count; $i++) {
            $line = $lines[$i];
            if (trim($line) === '') { $flush(); continue; }
            if (preg_match('/\A```([a-z0-9_-]*)\s*\z/i', $line, $match)) {
                $flush();
                $code = [];
                while (++$i < $count && !preg_match('/\A```\s*\z/', $lines[$i])) $code[] = $lines[$i];
                $id = 'doc-code-' . ++$this->codeIndex;
                $html .= '<div class="code-block"><button type="button" data-copy="' . $id . '">' . SampleI18n::e('copy') . '</button><pre><code id="' . $id . '" class="language-' . self::escape($match[1]) . '">' . self::escape(implode("\n", $code)) . '</code></pre></div>';
            } elseif (preg_match('/\A(#{1,6}) (.+)\z/', $line, $match)) {
                $flush();
                $level = strlen($match[1]);
                $html .= '<h' . $level . ' id="' . self::escape($this->heading($match[2])) . '">' . self::inline($match[2]) . '</h' . $level . '>';
            } elseif (str_starts_with($line, '|') && isset($lines[$i + 1]) && preg_match('/\A\|[\s:|\-]+\|\s*\z/', $lines[$i + 1])) {
                $flush();
                $html .= '<div class="table-wrap"><table><thead><tr>';
                foreach (self::cells($line) as $cell) $html .= '<th>' . self::inline(trim($cell)) . '</th>';
                $html .= '</tr></thead><tbody>';
                $i++;
                while (isset($lines[$i + 1]) && str_starts_with($lines[$i + 1], '|')) {
                    $html .= '<tr>';
                    foreach (self::cells($lines[++$i]) as $cell) $html .= '<td>' . self::inline(trim($cell)) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';
            } elseif (preg_match('/\A(?:([-*]) |\d+\. )(.+)\z/', $line, $match)) {
                $type = !empty($match[1]) ? 'ul' : 'ol';
                if ($list !== $type) { $flush(); $html .= '<' . $type . '>'; $list = $type; }
                $html .= '<li>' . self::inline($match[2]) . '</li>';
            } elseif (preg_match('/\A-{3,}\s*\z/', $line)) {
                $flush();
                $html .= '<hr>';
            } else {
                if ($list !== null) $flush();
                $paragraph[] = $line;
            }
        }
        $flush();
        return $html;
    }
}
