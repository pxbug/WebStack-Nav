<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$idx = rand(0, 7);
$url = 'https://cn.bing.com/HPImageArchive.aspx?format=js&idx=' . $idx . '&n=1&mkt=zh-CN';

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
    ],
]);

$body = @file_get_contents($url, false, $ctx);
$out = ['url' => null];

if ($body) {
    $start = strpos($body, '{');
    $end = strrpos($body, '}');
    if ($start !== false && $end > $start) {
        $json = substr($body, $start, $end - $start + 1);
        $data = json_decode($json, true);
        if (!empty($data['images'][0]['url'])) {
            $u = $data['images'][0]['url'];
            $out['url'] = (strpos($u, 'http') === 0) ? $u : 'https://cn.bing.com' . $u;
        }
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
