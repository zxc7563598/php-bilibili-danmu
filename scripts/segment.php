#!/usr/bin/env php
<?php

use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;

require_once __DIR__ . '/../vendor/autoload.php';

// 提高内存限制
ini_set('memory_limit', '512M');

// 初始化分词
Jieba::init();
Finalseg::init();

// 读取 stdin 中的 JSON 文本数组（形如 ["弹幕1", "弹幕2", ...]）
$stdin = file_get_contents("php://stdin");
$messages = json_decode($stdin, true);

if (!is_array($messages)) {
    fwrite(STDERR, "输入数据格式错误，必须是 JSON 数组。\n");
    exit(1);
}

// 分词并统计词频
$wordFrequency = [];

foreach ($messages as $msg) {
    if (!is_string($msg) || str_starts_with($msg, '[')) {
        continue;
    }
    $words = Jieba::cut($msg, true);
    foreach ($words as $word) {
        if (mb_strlen($word) < 2) continue;
        $wordFrequency[$word] = ($wordFrequency[$word] ?? 0) + 1;
    }
}

arsort($wordFrequency);
$topWords = array_slice($wordFrequency, 0, 50, true);

echo json_encode($topWords, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
