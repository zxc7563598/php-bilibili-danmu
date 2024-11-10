<?php

namespace app\server\core;

class KeywordMatcher
{
    private string $expression;
    private array $tokens;
    private int $position;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
        $this->tokens = $this->tokenize($expression);
        $this->position = 0;
    }

    // 1. 将表达式拆分成 token
    private function tokenize(string $expression): array
    {
        $pattern = '/(\|\||&&|\(|\)|\w+)/u';
        preg_match_all($pattern, $expression, $matches);
        return $matches[0];
    }

    // 2. 解析表达式
    public function parse(): array
    {
        return $this->parseOr();
    }

    private function parseOr(): array
    {
        $node = $this->parseAnd();
        while ($this->match('||')) {
            $right = $this->parseAnd();
            $node = ['operator' => '||', 'left' => $node, 'right' => $right];
        }
        return $node;
    }

    private function parseAnd(): array
    {
        $node = $this->parsePrimary();
        while ($this->match('&&')) {
            $right = $this->parsePrimary();
            $node = ['operator' => '&&', 'left' => $node, 'right' => $right];
        }
        return $node;
    }

    private function parsePrimary(): array
    {
        if ($this->match('(')) {
            $node = $this->parseOr();
            $this->match(')');
            return $node;
        }
        return ['keyword' => $this->consume()];
    }

    private function match(string $token): bool
    {
        if ($this->current() === $token) {
            $this->position++;
            return true;
        }
        return false;
    }

    private function consume(): string
    {
        return $this->tokens[$this->position++];
    }

    private function current(): ?string
    {
        return $this->tokens[$this->position] ?? null;
    }
}
