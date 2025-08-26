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
     * 设置全局数据格式
     *
     * @param array $data
     * @param bool $binary
     * @return array
     */
    public static function format(array $data, bool $binary = true): array
    {
        if ($binary) {
            $message['type']        = $data['type'];
            $message['sn']          = $data['sn'];
            $message['index']       = $data['index'];
            $message['count']       = $data['count'];
            $message['notice_type'] = $data['peer']['notice_type'];
            $message['sender']      = $data['peer']['sender'];
            $message['receiver']    = $data['peer']['receiver'];
            $message['group_code']  = $data['peer']['group_code'];
            $message['payload']     = $data['payload'];
        } else {
            $message = $data;
        }
        return $message;
    }
}
