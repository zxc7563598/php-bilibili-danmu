<?php

class PdDecoder
{
    private $buf;
    private $pos = 0;

    // 加载 Base64 数据
    public function loadBase64(string $b64): self
    {
        $this->buf = base64_decode($b64);
        $this->pos = 0;
        return $this;
    }

    // 加载二进制数据
    public function loadBuf(string $binary): self
    {
        $this->buf = $binary;
        $this->pos = 0;
        return $this;
    }

    // 遍历 Protobuf 字段（模拟 Go 的 iter.Seq）
    public function range(): Generator
    {
        while ($this->pos < strlen($this->buf)) {
            $tag = $this->readVarint();
            $fieldNum = $tag >> 3;      // 字段编号（如 2=用户名，5=消息类型）
            $wireType = $tag & 0x07;     // 数据类型（0=Varint, 2=Length-delimited）

            $pd = new Pd($this, $tag);
            yield $pd;  // 返回当前字段处理器

            // 如果字段未被处理，自动跳过
            if (!$pd->isDealed()) {
                $this->skipType($wireType);
            }
        }
    }

    // 读取 Varint（变长整数）
    private function readVarint(): int
    {
        $result = 0;
        $shift = 0;
        do {
            $byte = ord($this->buf[$this->pos++]);
            $result |= ($byte & 0x7F) << $shift;
            $shift += 7;
        } while ($byte & 0x80);
        return $result;
    }

    // 跳过字段
    private function skipType(int $wireType): void
    {
        switch ($wireType) {
            case 0: // Varint
                $this->readVarint();
                break;
            case 1: // 64-bit
                $this->pos += 8;
                break;
            case 2: // Length-delimited
                $len = $this->readVarint();
                $this->pos += $len;
                break;
            case 5: // 32-bit
                $this->pos += 4;
                break;
        }
    }

    // 读取 uint32
    public function readUint32(): int
    {
        return $this->readVarint();
    }

    // 读取 bytes/string
    public function readBytes(): string
    {
        $len = $this->readVarint();
        $data = substr($this->buf, $this->pos, $len);
        $this->pos += $len;
        return $data;
    }
}

class Pd
{
    private $decoder;
    private $tag;
    private $dealed = false;

    public function __construct(PdDecoder $decoder, int $tag)
    {
        $this->decoder = $decoder;
        $this->tag = $tag;
    }

    // 获取字段编号（如 2、5）
    public function type(): int
    {
        return $this->tag >> 3;
    }

    // 读取 uint32
    public function uint32(): int
    {
        $this->dealed = true;
        return $this->decoder->readUint32();
    }

    // 读取 bytes/string
    public function bytes(): string
    {
        $this->dealed = true;
        return $this->decoder->readBytes();
    }

    // 是否已处理该字段
    public function isDealed(): bool
    {
        return $this->dealed;
    }
}

$json_data = [
    '{"cmd":"INTERACT_WORD_V2","data":{"dmscore":34,"pb":"CMO+pE0SFeeCuOm4oeS4jeS4iuWunumqjOivviIDBgMBKAEwzaXXCjjFp5nDBkDN2ointzRKMgifs64TEBcaCeWPtue4guS8miDLqGkoy6hpMJK7ygI4/9GfA0ABSANgzaXXCmi/jewXYgIIAXiy8fmw16etpxiAAQOIAQKaAQCyAZwDCMO+pE0SbAoV54K46bih5LiN5LiK5a6e6aqM6K++EkpodHRwczovL2kwLmhkc2xiLmNvbS9iZnMvZmFjZS9jMjJjYjg1NTdjMDE0YmM2YzhlMDcyOTEyNWUxODRlMTk1ZGZjMjc2LmpwZ0IHIzAwRDFGMRq4AQoJ5Y+257iC5LyaEBcYy6hpIJK7ygIo/9GfAzDLqGk4wrYTSAFQn7OuE1gDYL+N7BdqSmh0dHBzOi8vaTAuaGRzbGIuY29tL2Jmcy9saXZlLzE0M2Y1ZWMzMDAzYjQwODBkMWI1ZjgxN2E5ZWZkY2E0NmQ2MzE5NDUucG5negkjNDNCM0UzQ0OCAQkjNDNCM0UzQ0OKAQkjNUZDN0Y0RkaSAQkjRkZGRkZGRkaaAQkjMDAzMDhDOTkiAggQMhcIAxITMjAyNS0wNy0xMCAyMzo1OTo1OTpPCO8NEkpodHRwczovL2kwLmhkc2xiLmNvbS9iZnMvbGl2ZS84MGY3MzI5NDNjYzMzNjcwMjlkZjY1ZTI2Nzk2MGQ1NjczNmE4MmVlLnBuZ7oBAA=="}}',
    '{"cmd":"INTERACT_WORD_V2","data":{"dmscore":16,"pb":"COSXgJy3u6YGEg/miJHmmK/lj7blsI/msaoiAgMBKAEwzaXXCjjZp5nDBkDn7o+D/TJKMAifs64TEAQaCeWPtue4guS8miCOrfICKI6t8gIwjq3yAjiOrfICQAFgzaXXCmiUCmICCAJ4uNutp6GoracYmgEAsgHaAQjkl4Cct7umBhJdCg/miJHmmK/lj7blsI/msaoSSmh0dHBzOi8vaTIuaGRzbGIuY29tL2Jmcy9mYWNlL2E2M2QzNjcyOTJlNzIxMjRlNTYwMDk3MDRjMTU4NzBjNzI3NDJkOTcuanBnGmoKCeWPtue4guS8mhAEGI6t8gIgjq3yAiiOrfICMI6t8gI4wrYTSAFQn7OuE2CUCnoJIzU3NjJBNzk5ggEJIzU3NjJBNzk5igEJIzU3NjJBNzk5kgEJI0ZGRkZGRkZGmgEJIzAwMEI3MDk5IgIIATIAugF2CkpodHRwczovL2kwLmhkc2xiLmNvbS9iZnMvbGl2ZS9hMDRhMGJlZmRiYjFlYzU4Y2IxZjAwYmRiMzAwNjcyMDFlYTdiNjE1LnBuZxIm6L+R5pyf5oqV5ZaC6L+H77yM5aSa5aSa5LiOVEHkupLliqjlkKcYAg=="}}',
    '{"cmd":"INTERACT_WORD_V2","data":{"dmscore":6,"pb":"CK/84T4SDOS4qOaFlemBpeS4qCIBASgBMM2l1wo4/s6ZwwZAw4mRgP0ySi4In7OuExAGGgnlj7bnuILkvJognvf1AijAgYMGMMCBgwY4wIGDBmDNpdcKaIAZYgB4vorH4M26rqcYmgEAsgHRAQiv/OE+EloKDOS4qOaFlemBpeS4qBJKaHR0cHM6Ly9pMC5oZHNsYi5jb20vYmZzL2ZhY2UvYjk4NWE4Y2MxN2VkMjZmMjI5YWJhOTIwN2EyZTgyYjM1YzEwNzcxZC5qcGcaaAoJ5Y+257iC5LyaEAYYwIGDBiDAgYMGKMCBgwYwnvf1AjjCthNQn7OuE2CAGXoJIzkxOTI5OENDggEJIzkxOTI5OENDigEJIzkxOTI5OENDkgEJI0ZGRkZGRkZGmgEJIzZDNkM3Mjk5IgIICjIAugF6CkpodHRwczovL2kwLmhkc2xiLmNvbS9iZnMvbGl2ZS9iYjg4NzM0NTU4YzYzODNhNGNmYjVmYTE2Yzk3NDlkNTI5MGQ5NWU4LnBuZxIq5pu+57uP5rS76LeD6L+H77yM6L+R5pyf5LiO5L2g5LqS5Yqo6L6D5bCRGAQ="}}',
    '{"cmd":"INTERACT_WORD_V2","data":{"dmscore":6,"pb":"CKqM1BASDOeBsOW/g+aer+W9oiIBASgBMM2l1wo4js+ZwwZA2IuSgP0ySi4In7OuExAHGgnlj7bnuILkvJognvf1AijAgYMGMMCBgwY4wIGDBmDNpdcKaPwqYgB43I/Z74u7rqcYmgEAsgHRAQiqjNQQEloKDOeBsOW/g+aer+W9ohJKaHR0cHM6Ly9pMi5oZHNsYi5jb20vYmZzL2ZhY2UvODU4MzVlNDlhODliMGNhOWNlYjNhNjBjMjY0NDQyOWRkZGFlYWU3YS5qcGcaaAoJ5Y+257iC5LyaEAcYwIGDBiDAgYMGKMCBgwYwnvf1AjjCthNQn7OuE2D8KnoJIzkxOTI5OENDggEJIzkxOTI5OENDigEJIzkxOTI5OENDkgEJI0ZGRkZGRkZGmgEJIzZDNkM3Mjk5IgIIBzIAugEA"}}',
    '{"cmd":"INTERACT_WORD_V2","data":{"dmscore":14,"pb":"COSXgJy3u6YGEg/miJHmmK/lj7blsI/msaoiAgMBKAEwzaXXCjiyz5nDBkDH/8WF/TJKMAifs64TEAQaCeWPtue4guS8miCOrfICKI6t8gIwjq3yAjiOrfICQAFgzaXXCmiUCmIAePnsxuaSvK6nGJoBALIB2gEI5JeAnLe7pgYSXQoP5oiR5piv5Y+25bCP5rGqEkpodHRwczovL2kyLmhkc2xiLmNvbS9iZnMvZmFjZS9hNjNkMzY3MjkyZTcyMTI0ZTU2MDA5NzA0YzE1ODcwYzcyNzQyZDk3LmpwZxpqCgnlj7bnuILkvJoQBBiOrfICII6t8gIojq3yAjCOrfICOMK2E0gBUJ+zrhNglAp6CSM1NzYyQTc5OYIBCSM1NzYyQTc5OYoBCSM1NzYyQTc5OZIBCSNGRkZGRkZGRpoBCSMwMDBCNzA5OSICCAEyALoBdgpKaHR0cHM6Ly9pMC5oZHNsYi5jb20vYmZzL2xpdmUvYTA0YTBiZWZkYmIxZWM1OGNiMWYwMGJkYjMwMDY3MjAxZWE3YjYxNS5wbmcSJui/keacn+aKleWWgui/h++8jOWkmuWkmuS4jlRB5LqS5Yqo5ZCnGAI="}}',
    '{"cmd":"INTERACT_WORD_V2","data":{"dmscore":16,"pb":"CJyW758CEgnkvIrkuYvliIciAgMBKAEwzaXXCjizz5nDBkDpiM6Q/TJKMQifs64TEAsaCeWPtue4guS8miCm+bUEKKb5tQQwpvm1BDim+bUEQAFgzaXXCmi0vwFiAHiWh66UlryupxiaAQCyAdIBCJyW758CElcKCeS8iuS5i+WIhxJKaHR0cHM6Ly9pMS5oZHNsYi5jb20vYmZzL2ZhY2UvODEyZTM0NWUxOWQ4MmMwOWViNjBjOWIwMTA0ODU5NGRmNjA5ODljZi5qcGcaawoJ5Y+257iC5LyaEAsYpvm1BCCm+bUEKKb5tQQwpvm1BDjCthNIAVCfs64TYLS/AXoJIzU5NkZFMDk5ggEJIzU5NkZFMDk5igEJIzU5NkZFMDk5kgEJI0ZGRkZGRkZGmgEJIzAwMEI3MDk5IgIIETIAugEA"}}',
];

foreach ($json_data as $json) {
    $data = json_decode($json, true);
    $pbBase64 = $data['data']['pb'];

    // 使用 PdDecoder 解析 Protobuf
    $decoder = (new PdDecoder())->loadBase64($pbBase64);

    $msgType = 0;
    $uname = '';
    foreach ($decoder->range() as $pd) {
        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo $pd->bytes() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;

        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo $pd->uint32() . PHP_EOL;
        echo PHP_EOL . PHP_EOL . PHP_EOL;
        break;
    }
}

// echo "[事件{$msgType}] 用户名: " . $uname . PHP_EOL;
