# Laravel-WebSocket 🔐

Laravel websocket 扩展包。

## ⏳ 安装准备

1. PHP版本 >= ^8.0
2. swoole版本 >= 4.8.*
3. predis/predis版本 *
4. redis版本 *

## 📦 安装

```bash
composer require layman/laravel-websocket
```

## ⚙️ 发布配置

```bash
php artisan vendor:publish --provider="Layman\LaravelWebsocket\WebSocketServiceProvider" --tag=websocket
php artisan vendor:publish --tag=websocket-view
```

## 🛠️ 启动命令

```bash
php artisan websocket:start
```

## 📋 二进制消息协议数据格式

| 字段名           | 字段是否必传 | 数据类型                  | 说明                                                                                   |
|---------------|--------|-----------------------|--------------------------------------------------------------------------------------|
| type          | 是      | int                   | 消息类型：<br>1：文本<br>2：图片<br>3：文件<br>4：视屏<br>5：控制消息<br>6：音频<br>7：表情/动画<br>8：消息确认<br>9：其他 |
| sn            | 是      | string                | 消息序列号uuid                                                                            |
| index         | 是      | int                   | 分片索引                                                                                 |
| count         | 是      | int                   | 分片总数                                                                                 |
| peer          | 是      | array                 | 发送者&接收者&群编号&通知类型                                                                     |
| └─sender      | 是      | int/string            | 发送者                                                                                  |
| └─receiver    | 是      | int/string/array/null | 接收者                                                                                  |
| └─group_code  | 是      | int/string/null       | 群编号                                                                                  |
| └─notice_type | 是      | int                   | 通知类型：<br>1：私聊<br>2：群聊<br>3：系统<br>4：广播<br>                                         |
| payload       | 是      | string                | 消息内容                                                                                 |

## 📋 普通文本消息数据格式

| 字段名         | 字段是否必传 | 数据类型                  | 说明                                                                                   |
|-------------|--------|-----------------------|--------------------------------------------------------------------------------------|
| type        | 是      | int                   | 消息类型：<br>1：文本<br>2：图片<br>3：文件<br>4：视屏<br>5：控制消息<br>6：音频<br>7：表情/动画<br>8：消息确认<br>9：其他 |
| sn          | 是      | string                | 消息序列号uuid                                                                            |
| index       | 是      | int                   | 分片索引                                                                                 |
| count       | 是      | int                   | 分片总数                                                                                 |
| sender      | 是      | int/string            | 发送者                                                                                  |
| receiver    | 是      | int/string/array/null | 接收者                                                                                  |
| group_code  | 是      | int/string/null       | 群编号                                                                                  |
| notice_type | 是      | int                   | 通知类型：<br>1：私聊<br>2：群聊<br>3：系统<br>4：广播<br>                                        |
| payload     | 是      | string                | 消息内容                                                                                 |

## 🚀 使用

```php

// 测试
Illuminate\Support\Facades\Route::get('/websocket', function () {
    return view('websocket');
});

// 消息订阅
$client = new \Predis\Client();
$client->publish('redis_subscribe_channel', json_encode([
    'group_id' => null,
    'type' => 'broadcast',
    'from' => 'system',
    'to' => ['xxx', 'xxxx'],
    'classify' => 'remind',
    'content' => 'broadcast',
    'extra' => null,
]));

// auth认证
class Auth implements WebSocketAuthInterface
{
    
}
```

## 免责声明

- 扩展包作者不对本工具的安全性、完整性、可靠性、有效性、正确性或适用性做任何明示或暗示的保证，也不对本扩展包的使用造成的任何直接或间接的损失、责任、索赔、要求或诉讼承担任何责任。
- 扩展包作者保留随时修改、更新、删除权利，无需事先通知或承担任何义务。
- 使用者在下载、安装、运行或使用本扩展包时，即表示已阅读并同意本免责声明。如有异议，请立即停止使用本扩展包，并删除所有相关文件。

## 🙌 支持与贡献

欢迎提 Issue 或 PR 来改进此包。你的每一个建议和贡献，都是我们前进的动力！

如果你觉得 Laravel-WebSocket 有帮助，别忘了点个 ⭐ Star 哦！

