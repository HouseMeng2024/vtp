<?php
declare (strict_types = 1);

namespace app\worker;

class Events
{
    /**
     * WebSocket 客户端连接成功事件。
     */
    public static function onConnect(string $clientId): void
    {
    }

    /**
     * WebSocket 客户端消息事件。
     */
    public static function onMessage(string $clientId, mixed $message): void
    {
    }

    /**
     * WebSocket 客户端断开连接事件。
     */
    public static function onClose(string $clientId): void
    {
    }
}
