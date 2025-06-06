# Laravel-WebSocket 🔐

Laravel websocket 扩展包。

## 📦 安装

```bash
composer require layman/laravel-websocket
```

## ⚙️ 发布配置

```bash
php artisan vendor:publish --provider=" Layman\LaravelWebsocket\WebSocketServiceProvider" --tag=websocket
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
```

## 🙌 支持与贡献

欢迎提 Issue 或 PR 来改进此包。你的每一个建议和贡献，都是我们前进的动力！

如果你觉得 Laravel-WebSocket 有帮助，别忘了点个 ⭐ Star 哦！

