<?php

use Hejunjie\Tools;

/**
 * 读取文件信息
 * 
 * @param string $path 文件路径
 * 
 * @return null|string 
 */
function readFileContent(string $path): ?string
{
    return (file_exists($path) && is_readable($path)) ? Tools\FileUtils::readFile($path) : null;
}
