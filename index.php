<?php

/**
 * Fasync Framework Web Demo
 *
 * This self-contained file showcases the 'In-Request Concurrency' of the
 * Fasync HTTP client by comparing it to a standard, synchronous approach.
 * It uses a simple router and separates the main PHP logic from the HTML output.
 *
 * To run:
 * 1. Ensure guzzlehttp/guzzle is installed: `composer require guzzlehttp/guzzle`
 * 2. Run from your project root: `php -S localhost:8000 -t public`
 * 3. Open your browser to `http://localhost:8000`
 */

require_once 'vendor/autoload.php';

use Rcalicdan\FiberAsync\Api\Http;
use GuzzleHttp\Client as GuzzleClient;

// ======================================================================
// 1. PHP LOGIC & DATA FETCHING
// ======================================================================

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/run-demo') {
    $apiCalls = [
        'user_1'  => 'https://jsonplaceholder.typicode.com/users/1',
        'posts_1' => 'https://jsonplaceholder.typicode.com/posts?userId=1',
        'todos_1' => 'https://jsonplaceholder.typicode.com/todos?userId=1',
        'user_2'  => 'https://jsonplaceholder.typicode.com/users/2',
        'posts_2' => 'https://jsonplaceholder.typicode.com/posts?userId=2',
        'album_3' => 'https://jsonplaceholder.typicode.com/albums/3',
    ];

    $syncResults = [];
    $syncTimeline = [];
    $syncStartTime = microtime(true);
    $guzzleClient = new GuzzleClient();

    foreach ($apiCalls as $key => $url) {
        $response = $guzzleClient->get($url);
        $syncResults[$key] = json_decode((string)$response->getBody(), true);
        $syncTimeline[$key] = microtime(true) - $syncStartTime;
    }
    $syncTotalTime = microtime(true) - $syncStartTime;
    $asyncPromises = [];
    $asyncTimeline = [];
    $asyncStartTime = microtime(true);

    foreach ($apiCalls as $key => $url) {
        $asyncPromises[$key] = Http::get($url)
            ->then(function ($response) use (&$asyncTimeline, $key, $asyncStartTime) {
                $asyncTimeline[$key] = microtime(true) - $asyncStartTime;
                return $response->json();
            });
    }

    $asyncResults = run_all($asyncPromises);
    $asyncTotalTime = microtime(true) - $asyncStartTime;

    $improvement = (($syncTotalTime - $asyncTotalTime) / $syncTotalTime) * 100;
    $speedup = $syncTotalTime / $asyncTotalTime;

    uasort($asyncTimeline, fn($a, $b) => $a <=> $b);

    function renderBar(string $key, float $finishTime, float $totalTime, array $allCalls): string
    {
        $width = ($finishTime / $totalTime) * 100;
        $label = sprintf("%s (%.0fms)", $key, $finishTime * 1000);
        $colors = ['#0d6efd', '#198754', '#0dcaf0', '#ffc107', '#fd7e14', '#6f42c1'];
        $colorIndex = array_search($key, array_keys($allCalls)) % count($colors);
        $color = $colors[$colorIndex];

        return "<div class='bar' style='width: {$width}%; background-color: {$color};' title='{$label}'>{$label}</div>";
    }
}

// ======================================================================
// 2. HTML PRESENTATION
// ======================================================================

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fasync Web Demo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
            background-color: #f8f9fa;
        }

        h1,
        h2 {
            color: #212529;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            background-color: #0d6efd;
            color: #fff;
            border-radius: 5px;
            text-align: center;
        }

        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            max-height: 200px;
            overflow-y: auto;
        }

        .results-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (min-width: 768px) {
            .results-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .result-card {
            border: 1px solid #dee2e6;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .result-card h2 {
            margin-top: 0;
        }

        .timeline {
            position: relative;
            width: 100%;
            height: 210px;
            background: #e9ecef;
            border-radius: 5px;
            padding: 10px;
            box-sizing: border-box;
        }

        .bar {
            height: 30px;
            line-height: 30px;
            color: white;
            padding-left: 10px;
            margin-bottom: 5px;
            border-radius: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
            font-weight: bold;
        }

        .total-time {
            font-weight: bold;
            font-size: 24px;
        }

        .sync-time {
            color: #dc3545;
        }

        .async-time {
            color: #198754;
        }

        .summary {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: #d1e7dd;
            border-left: 5px solid #198754;
        }
    </style>
</head>

<body>

    <div class="container">
        <?php if ($requestPath === '/'): ?>

            <h1>🚀 Fasync Framework Web Demo</h1>
            <p>This is a demonstration of the Fasync framework's capabilities, particularly its high-performance, asynchronous HTTP client.</p>
            <h2>HTTP Client Showcase</h2>
            <p>The following demo will make 6 API calls to the public <a href="https://jsonplaceholder.typicode.com/" target="_blank">JSONPlaceholder</a> service. It will run them first sequentially (the traditional, slow way) and then concurrently (the Fasync way) and compare the results.</p>
            <p>This will visually demonstrate how Fasync's 'In-Request Concurrency' can dramatically speed up I/O-bound web requests, even in a standard PHP environment.</p>
            <p><a href="/run-demo" class="btn">Run the Demo &rarr;</a></p>

        <?php elseif ($requestPath === '/run-demo'): ?>

            <h1>📊 Fasync HTTP Client Benchmark</h1>
            <p><a href="/">&larr; Back to Home</a></p>

            <div class="results-grid">
                <div class="result-card">
                    <h2>Synchronous (Traditional)</h2>
                    <div class="total-time sync-time">Total: <?= sprintf("%.2fms", $syncTotalTime * 1000) ?></div>
                    <div class="timeline">
                        <?php foreach ($syncTimeline as $key => $time) echo renderBar($key, $time, $syncTotalTime, $apiCalls); ?>
                    </div>
                    <details>
                        <summary>View first result data</summary>
                        <pre><?= htmlspecialchars(json_encode($syncResults['user_1'], JSON_PRETTY_PRINT)) ?></pre>
                    </details>
                </div>

                <div class="result-card">
                    <h2>Asynchronous (Fasync)</h2>
                    <div class="total-time async-time">Total: <?= sprintf("%.2fms", $asyncTotalTime * 1000) ?></div>
                    <div class="timeline">
                        <?php foreach ($asyncTimeline as $key => $time) echo renderBar($key, $time, $asyncTotalTime, $apiCalls); ?>
                    </div>
                    <details>
                        <summary>View first result data</summary>
                        <pre><?= htmlspecialchars(json_encode($asyncResults['user_1'], JSON_PRETTY_PRINT)) ?></pre>
                    </details>
                </div>
            </div>

            <div class="summary">
                <h2>Conclusion</h2>
                <p>The synchronous method executed the 6 API calls sequentially. The asynchronous Fasync method dispatched all 6 requests at once and waited only for the slowest one to finish. The result is a dramatic **<?= sprintf("%.1f%% reduction in execution time (%.2fx faster)", $improvement, $speedup) ?>**.</p>
                <p>This demonstrates the power of Fasync's 'In-Request Concurrency' for speeding up I/O-bound web pages.</p>
            </div>

        <?php else: ?>
            <?php http_response_code(404); ?>
            <h1>404 Not Found</h1>
            <p>The requested page could not be found.</p>
            <p><a href="/">&larr; Back to Home</a></p>
        <?php endif; ?>
    </div>

</body>

</html>