<?php

namespace app\server\core;

class KeywordEvaluator
{
    private array $tree;
    private string $message;

    public function __construct(array $tree, string $message)
    {
        $this->tree = $tree;
        $this->message = $message;
    }

    // 递归解析表达式树
    public function evaluate(): bool
    {
        return $this->evaluateNode($this->tree);
    }

    private function evaluateNode(array $node): bool
    {
        if (isset($node['keyword'])) {
            // 关键词匹配
            return mb_strpos($this->message, $node['keyword']) !== false;
        }
        $left = $this->evaluateNode($node['left']);
        $right = $this->evaluateNode($node['right']);
        if ($node['operator'] === '||') {
            return $left || $right;
        } elseif ($node['operator'] === '&&') {
            return $left && $right;
        }
        return false;
    }
}
