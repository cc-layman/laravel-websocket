# Laravel-WebSocket 🔐

Laravel websocket 扩展包。

## ⏳ 安装准备

1. PHP版本 >= ^8.1
2. swoole版本 >= 6.*
3. predis/predis 版本 >= 3.*
4. redis版本 >= 7.*

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

## 🚀 使用

```php
use Illuminate\Support\Facades\Route;

// 测试
Route::get('/websocket', function () {
    return view('websocket');
});

// 数据格式
json_encode([
    // {私聊：private}{群聊：group}  消息订阅：{广播个人：notice}{广播群组：broadcast}{广播现在线所有连接：online}
    'type' => 'notice',
    // 发送者
    'from' => 'system', 
    // 收到者
    'to' => 'xxxx',
    // 消息内容
    'content' => '数据格式',
    // 扩展内容
    'extra' => null|array,
])

// 实现auth认证
class Auth implements WebSocketAuthInterface
{
    
}
```

## 🙌 支持与贡献

欢迎提 Issue 或 PR 来改进此包。你的每一个建议和贡献，都是我们前进的动力！

如果你觉得 Laravel-WebSocket 有帮助，别忘了点个 ⭐ Star 哦！

