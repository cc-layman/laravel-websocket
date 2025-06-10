<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>WebSocket 测试</title>
</head>
<body>
<h1>WebSocket 测试</h1>

<p>请输入 UserID: <input type="text" id="useridInput" placeholder="输入你的UserID" /></p>
<button id="connectBtn">连接</button>

<p>当前 UserID: <span id="useridDisplay">未连接</span></p>
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
    let ws = null;
    let userid = null;

    document.getElementById('connectBtn').addEventListener('click', () => {
        const inputId = document.getElementById('useridInput').value.trim();
        if (!inputId) {
            alert('请输入UserID');
            return;
        }

        userid = inputId;
        document.getElementById('useridDisplay').innerText = userid;

        // 建立 WebSocket 连接
        ws = new WebSocket('ws://127.0.0.1:9502?userid=' + userid);

        ws.onopen = function () {
            document.getElementById('status').innerText = '连接状态：已连接';
            console.log('WebSocket 已连接');

            // 心跳
            setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    const pingPayload = {
                        type: 'ping',
                        to: 0,
                        from: userid,
                        content: 'ping'
                    };
                    ws.send(JSON.stringify(pingPayload));
                }
            }, 3000);
        };

        ws.onmessage = function (event) {
            const data = JSON.parse(event.data);
            // 如果是 pong 消息，则不展示
            if (data.type === 'pong') {
                return;
            }
            const msgList = document.getElementById('messages');
            const item = document.createElement('li');
            item.textContent = event.data;
            msgList.appendChild(item);
        };

        ws.onclose = function () {
            document.getElementById('status').innerText = '连接状态：已关闭';
            console.log('WebSocket 连接已关闭');
            document.getElementById('useridDisplay').innerText = '未连接';
        };

        ws.onerror = function (error) {
            console.error('WebSocket 错误:', error);
        };
    });

    function sendMessage() {
        if (!ws || ws.readyState !== WebSocket.OPEN) {
            alert('请先连接WebSocket');
            return;
        }

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
