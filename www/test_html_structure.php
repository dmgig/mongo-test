<?php

require_once dirname(__DIR__) . '/settings.php';

use App\Infrastructure\Html\HtmlStructureService;

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Page</title>
</head>
<body class="home-page">
    <header id="main-header" class="site-header">
        <h1>Logo</h1>
        <nav class="main-nav">
            <ul>
                <li><a href="#">Home</a></li>
            </ul>
        </nav>
    </header>
    <main class="content-wrapper">
        <article class="post featured">
            <h2 class="post-title">Hello World</h2>
            <div class="post-body">
                <p>This is some text.</p>
                <p>More text.</p>
            </div>
        </article>
        <aside class="sidebar">
            <div class="widget">Widget Content</div>
        </aside>
    </main>
    <footer class="site-footer">
        <p>Copyright</p>
    </footer>
</body>
</html>
HTML;

$service = new HtmlStructureService();
echo "--- Original Structure Extraction ---\n";
echo $service->extractStructure($html) . "\n";
