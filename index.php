<?php
declare(strict_types=1);

$dataPath = __DIR__ . '/data/content.json';
$raw = file_get_contents($dataPath);
if ($raw === false) {
    throw new RuntimeException('Unable to load content data.');
}

$data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

function headingTag(int $depth): string
{
    return match ($depth) {
        0 => 'h2',
        1 => 'h3',
        2 => 'h4',
        3 => 'h5',
        default => 'h6',
    };
}

function renderContent(array $items): string
{
    $html = '';

    foreach ($items as $item) {
        $type = $item['type'] ?? 'paragraph';

        switch ($type) {
            case 'paragraph':
                $text = htmlspecialchars($item['text'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $html .= "<p>{$text}</p>";
                break;
            case 'infoCard':
                $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $body = '';
                $entries = $item['body'] ?? [];
                if (!empty($entries)) {
                    $listTag = (($item['listStyle'] ?? 'unordered') === 'ordered') ? 'ol' : 'ul';
                    $bodyItems = array_map(
                        static fn($entry) => '<li>' . htmlspecialchars((string) $entry, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>',
                        $entries
                    );
                    $body = '<' . $listTag . '>' . implode('', $bodyItems) . '</' . $listTag . '>';
                }
                $html .= "<div class=\"info-card\"><h4>{$title}</h4>{$body}</div>";
                break;
            case 'table':
                $headers = $item['headers'] ?? [];
                $rows = $item['rows'] ?? [];
                $thead = '';
                if ($headers) {
                    $headerCells = array_map(
                        static fn($header) => '<th>' . htmlspecialchars((string) $header, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</th>',
                        $headers
                    );
                    $thead = '<thead><tr>' . implode('', $headerCells) . '</tr></thead>';
                }

                $tbodyRows = [];
                foreach ($rows as $row) {
                    $cells = array_map(
                        static fn($cell) => '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>',
                        $row
                    );
                    $tbodyRows[] = '<tr>' . implode('', $cells) . '</tr>';
                }
                $tbody = '<tbody>' . implode('', $tbodyRows) . '</tbody>';
                $html .= '<div class="table-container"><table>' . $thead . $tbody . '</table></div>';
                break;
            case 'list':
                $style = $item['style'] ?? 'unordered';
                $tag = $style === 'ordered' ? 'ol' : 'ul';
                $itemsHtml = array_map(
                    static fn($value) => '<li>' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>',
                    $item['items'] ?? []
                );
                $html .= '<' . $tag . '>' . implode('', $itemsHtml) . '</' . $tag . '>';
                break;
            default:
                $text = htmlspecialchars($item['text'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if ($text !== '') {
                    $html .= "<p>{$text}</p>";
                }
                break;
        }
    }

    return $html;
}

function renderSection(array $section, int $depth = 0): string
{
    $id = htmlspecialchars($section['id'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $number = htmlspecialchars($section['number'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $title = htmlspecialchars($section['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $content = renderContent($section['content'] ?? []);
    $tag = headingTag($depth);

    $html = "<section id=\"{$id}\" class=\"section\"><{$tag}>{$number} {$title}</{$tag}>{$content}";

    if (!empty($section['subsections'])) {
        foreach ($section['subsections'] as $subsection) {
            $html .= renderSection($subsection, $depth + 1);
        }
    }

    $html .= '</section>';

    return $html;
}

function buildToc(array $sections, int $depth = 0): string
{
    $html = '<ul class="toc-list">';

    foreach ($sections as $section) {
        $id = htmlspecialchars($section['id'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $number = htmlspecialchars($section['number'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $title = htmlspecialchars($section['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $class = 'toc-item';
        if ($depth === 1) {
            $class .= ' toc-item-2';
        } elseif ($depth >= 2) {
            $class .= ' toc-item-3';
        }

        $html .= "<li class=\"{$class}\"><a href=\"#{$id}\">{$number} {$title}</a>";

        if (!empty($section['subsections'])) {
            $html .= buildToc($section['subsections'], $depth + 1);
        }

        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ścieżka rozwoju w fizjoterapii sportowej</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="cover-page">
            <div class="cover-content">
                <h1><?= htmlspecialchars($data['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
                <h2 class="cover-subtitle">
                    <?= htmlspecialchars($data['subtitle'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </h2>
                <div class="cover-meta">
                    <?php foreach ($data['meta'] as $metaLine): ?>
                        <p><?= htmlspecialchars((string) $metaLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="scroll-indicator" type="button" aria-label="Przewiń do nawigacji">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 10l5 5 5-5z"></path>
                </svg>
            </button>
        </div>

        <div class="nav">
            <div class="nav-title"><?= htmlspecialchars($data['navTitle'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
            <button class="nav-toggle" data-toggle="toc" type="button" aria-expanded="false" aria-controls="toc-panel">Spis treści</button>
        </div>

        <div class="toc-overlay"></div>
        <aside id="toc-panel" class="toc-sidebar" role="dialog" aria-label="Spis treści" aria-hidden="true">
            <div class="toc-header">Spis treści</div>
            <?= buildToc($data['sections']); ?>
        </aside>

        <div class="content">
            <?php foreach ($data['sections'] as $section): ?>
                <?= renderSection($section); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
