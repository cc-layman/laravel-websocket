<?php

namespace Layman\LaravelWebsocket\Cores;

class Utils
{
    /**
     * 服务器唯一标识
     *
     * @param string|int|null $key
     * @return string
     */
    public static function sid(string|int $key = null): string
    {
        $hostname = gethostname();
        $intranet = gethostbyname($hostname);
        $sid      = sprintf('sid-%s', md5(sprintf('%s%s%s', $hostname, $intranet, env('APP_KEY'))));
        if (is_null($key)) {
            return $sid;
        }
        return sprintf("{$sid}#%s", $key);
    }


    /**
     * 打包
     *
     * @param int $type 消息类型
     * @param string $sn 消息唯一编号
     * @param int $index 当前分片序号,从1开始
     * @param int $count 当前消息总分片数
     * @param array $peer 发送者和接收者和消息类型和群编号
     * @param string $payload 消息内容
     * @return string 二进制数据
     */
    public static function pack(int $type, string $sn, int $index, int $count, array $peer, string $payload): string
    {
        // 将 peer 转为 JSON
        $peerJson   = json_encode($peer);
        $peerLength = strlen($peerJson);

        // UUID: 36字符 → 16字节
        $snb = hex2bin(str_replace('-', '', $sn));

        // 打包格式:type(1字节消息类型) + sn(4字节消息编号) + index(2字节分片序号) + count(2字节总分片数) + peerLength(2字节peer长度) + peerJson + payload
        return
            chr($type) .
            $snb .
            pack('n', $index) .
            pack('n', $count) .
            pack('n', $peerLength) .
            $peerJson .
            $payload;
    }

    /**
     * 解包
     *
     * @param string $data
     * @return array
     */
    public static function unpack(string $data): array
    {
        $offset = 0;

        $type   = ord($data[$offset]);
        $offset += 1;

        // 读取 16 字节 UUID
        $snBinary = substr($data, $offset, 16);
        $offset   += 16;

        // 转回标准 UUID 字符串
        $snHex = bin2hex($snBinary);
        $sn    = substr($snHex, 0, 8) . '-' . substr($snHex, 8, 4) . '-' . substr($snHex, 12, 4) . '-' . substr($snHex, 16, 4) . '-' . substr($snHex, 20, 12);

        $index  = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;

        $count  = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;

        $peerLength = unpack('n', substr($data, $offset, 2))[1];
        $offset     += 2;

        $peerJson = substr($data, $offset, $peerLength);
        $peer     = json_decode($peerJson, true) ?: [];
        $offset   += $peerLength;

        $payload = substr($data, $offset);

        return [
            'type' => $type,
            'sn' => $sn,
            'index' => $index,
            'count' => $count,
            'notice_type' => $peer['notice_type'],
            'sender' => $peer['sender'],
            'receiver' => $peer['receiver'],
            'group_code' => $peer['group_code'],
            'payload' => $payload,
        ];
    }
}
