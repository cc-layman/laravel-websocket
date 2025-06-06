<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>WebSocket 测试</title>
</head>
<body>
<h1>WebSocket 测试</h1>

<p>当前 UserID: <span id="userid"></span></p>
<p id="status">连接状态：未连接</p>

<!-- 消息发送表单 -->
<div>
    <label for="to_userid">发送给 UserID：</label>
    <input type="text" id="to_userid" placeholder="接收方用户ID">
    <br><br>
    <label for="message">消息内容：</label>
    <input type="text" id="message" placeholder="输入消息">
    <button onclick="sendMessage()">发送消息</button>
</div>

<hr>
<div>
    <h3>收到的消息：</h3>
    <ul id="messages"></ul>
</div>

<script>
    const userid = Date.now(); // 当前用户唯一ID
    document.getElementById('userid').innerText = userid;

    const ws = new WebSocket('ws://127.0.0.1:9502?userid=' + userid);

    ws.onopen = function () {
        document.getElementById('status').innerText = '连接状态：已连接';
        console.log('WebSocket 已连接');
    };

    ws.onmessage = function (event) {
        const msgList = document.getElementById('messages');
        const item = document.createElement('li');
        item.textContent = event.data;
        msgList.appendChild(item);
    };

    ws.onclose = function () {
        document.getElementById('status').innerText = '连接状态：已关闭';
        console.log('WebSocket 连接已关闭');
    };

    ws.onerror = function (error) {
        console.error('WebSocket 错误:', error);
    };

    function sendMessage() {
        const toUserId = document.getElementById('to_userid').value.trim();
        const message = document.getElementById('message').value.trim();

        if (!toUserId || !message) {
            alert('请填写接收用户ID和消息内容');
            return;
        }

        const payload = {
            type: 'private',
            to: toUserId,
            from: userid,
            content: message
        };

        ws.send(JSON.stringify(payload));
    }
</script>
</body>
</html>
