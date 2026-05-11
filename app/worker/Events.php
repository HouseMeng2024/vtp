<?php
declare (strict_types = 1);

namespace app\worker;

use GatewayWorker\Lib\Gateway;

class Events
{
    /**
     * WebSocket 客户端连接成功后推送连接信息。
     */
    public static function onConnect(string $clientId): void
    {
        Gateway::sendToClient($clientId, json_encode([
            'type'      => 'connect',
            'client_id' => $clientId,
            'time'      => date('H:i:s'),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 接收客户端聊天消息并广播给所有在线客户端。
     */
    public static function onMessage(string $clientId, mixed $message): void
    {
        $payload = json_decode((string) $message, true);

        if (!is_array($payload)) {
            $payload = [];
        }

        $name = trim((string) ($payload['name'] ?? '游客'));
        $text = trim((string) ($payload['message'] ?? $message));

        if ($text === '') {
            return;
        }

        Gateway::sendToAll(json_encode([
            'type'      => 'message',
            'client_id' => $clientId,
            'name'      => mb_substr($name === '' ? '游客' : $name, 0, 20),
            'message'   => mb_substr($text, 0, 500),
            'time'      => date('H:i:s'),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 客户端断开连接后广播离线事件。
     */
    public static function onClose(string $clientId): void
    {
        Gateway::sendToAll(json_encode([
            'type'      => 'close',
            'client_id' => $clientId,
            'time'      => date('H:i:s'),
        ], JSON_UNESCAPED_UNICODE));
    }
}
