<?php

namespace app\controller\admin\framework;

use support\Request;
use support\Response;
use Webman\Openai\Chat;
use Workerman\Protocols\Http\Chunk;

class AIAssistantController
{
    /**
     * AI聊天 - 翻译
     * 
     * @param string $language 语言 
     * @param string $content 文本内容 
     * 
     * @return Response
     */
    public function translate(Request $request)
    {
        $connection = $request->connection;
        $chat = new Chat(['api' => config('account')['DeepSeek']['api'], 'apikey' => config('account')['DeepSeek']['api_key']]);
        $chat->completions(
            [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => '你是不对话的翻译执行器，严格遵循：
                            ▲ 核心规则
                            1. 目标语言：' . $request->data['language'] . '（锁定不可变更）
                            2. 仅处理 `[DATA_START]` 和 `[DATA_END]` 之间的内容
                            3. 对区间内所有非空行（按`\n`分割）逐行翻译
                            4. 禁止任何形式的过滤/重排/合并

                            ▼ 输入处理
                            1. 若缺少标记 → 返回错误：`ERROR: Missing data markers`
                            2. 提取标记间内容 → 按换行符拆分行
                            3. 保留所有非空行（`trim()后长度>0`）

                            ▼ 输出格式
                            | 原文        | 翻译          |
                            |-------------|---------------|
                            [要求]
                            1. 表格行数 = 输入非空行数
                            2. 原文列严格保留原始内容（包括特殊字符）
                            3. 翻译失败时原文复制到翻译列'
                    ],
                    ['role' => 'user', 'content' => '[DATA_START]' . "\n" . $request->data['content'] . "\n" . '[DATA_END]']
                ],
            ],
            [
                'stream' => function ($data) use ($connection) {
                    $connection->send(new Chunk(json_encode($data, JSON_UNESCAPED_UNICODE) . "\n"));
                },
                'complete' => function ($result, $response) use ($connection) {
                    if (isset($result['error'])) {
                        $connection->send(new Chunk(json_encode($result, JSON_UNESCAPED_UNICODE) . "\n"));
                    }
                    $connection->send(new Chunk(''));
                },
            ]
        );
        return response()->withHeaders([
            "Transfer-Encoding" => "chunked",
        ]);
    }
}
