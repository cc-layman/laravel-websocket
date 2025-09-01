<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>WebSocket 聊天（大文件支持）</title>
    <style>
        body {margin:0;font-family:Arial,sans-serif;background:#ece5dd;height:100vh;display:flex;justify-content:center;align-items:center;}
        #chatBox{width:800px;height:600px;background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;flex-direction:column;overflow:hidden;}
        #connectArea{padding:10px 20px;background:#075e54;color:white;display:flex;align-items:center;gap:10px;}
        #connectArea input{padding:5px 10px;border-radius:5px;border:none;flex:1;}
        #connectArea button{background:#25d366;border:none;color:#fff;padding:5px 12px;border-radius:5px;cursor:pointer;}
        #connectArea button:hover{background:#1ebe5a;}
        #receiverArea{padding:8px 20px;background:#f0f0f0;display:none;gap:10px;align-items:center;}
        #receiverArea input{flex:1;padding:5px 10px;border-radius:5px;border:1px solid #ccc;}
        #messages{flex:1;padding:20px;overflow-y:auto;background:#e5ddd5;display:flex;flex-direction:column;gap:10px;}
        .message{max-width:60%;padding:10px 14px;border-radius:7px;word-wrap:break-word;white-space:pre-wrap;font-size:14px;line-height:1.4;}
        .message.self{background:#dcf8c6;align-self:flex-end;}
        .message.other{background:#fff;border:1px solid #ddd;align-self:flex-start;}
        .message img,.message video,.message audio{display:block;margin-top:5px;max-width:200px;border-radius:5px;}
        .message video,.message audio{max-width:250px;}
        #inputArea{display:flex;gap:10px;padding:10px 20px;border-top:1px solid #ccc;background:#f7f7f7;}
        #inputArea input[type="text"]{flex:1;padding:7px 10px;border-radius:5px;border:1px solid #ccc;}
        #inputArea input[type="file"]{border:none;}
        #inputArea button{padding:7px 15px;border-radius:5px;border:none;background:#007bff;color:white;cursor:pointer;}
        #inputArea button:hover{background:#0056b3;}
        .progress-bar{width:100%;background:#ccc;height:4px;margin-top:3px;border-radius:2px;overflow:hidden;}
        .progress-bar-inner{height:100%;width:0%;background:#25d366;transition:width 0.2s;}
    </style>
</head>
<body>

<div id="chatBox">
    <div id="connectArea">
        <input type="text" id="useridInput" placeholder="输入你的UserID">
        <button id="connectBtn">连接</button>
        <span id="status">未连接</span>
    </div>
    <div id="receiverArea">
        <label>发送给:</label>
        <input type="text" id="receiver" placeholder="接收方用户ID/数组/null">
    </div>
    <div id="messages"></div>
    <div id="inputArea">
        <input type="text" id="payload" placeholder="输入消息">
        <input type="file" id="fileInput" accept="*/*">
        <button id="sendBtn">发送</button>
    </div>
</div>

<script type="module">
    import { WsClient } from '/js/ws-client.js';

    let client = null;
    let userid = null;

    // 消息渲染函数
    function renderMessageContent(msg) {
        const container = document.createElement('div');

        if(msg.type === 1){
            if(typeof msg.payload === 'object'){
                container.textContent = JSON.stringify(msg.payload, null, 2);
            } else {
                container.textContent = msg.payload;
            }
        } else if([2,3,4,6].includes(msg.type)){
            const blob = msg.payload;
            switch(msg.type){
                case 2: { const img=document.createElement('img'); img.src=URL.createObjectURL(blob); container.appendChild(img); break; }
                case 3: { const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=msg.files?.[0]?.name ? encodeURIComponent(msg.files[0].name) : 'download_file'; a.textContent=`点击下载: ${msg.files?.[0]?.name || '文件'}`; container.appendChild(a); break; }
                case 4: { const video=document.createElement('video'); video.src=URL.createObjectURL(blob); video.controls=true; container.appendChild(video); break; }
                case 6: { const audio=document.createElement('audio'); audio.src=URL.createObjectURL(blob); audio.controls=true; container.appendChild(audio); break; }
            }
        }

        return container;
    }

    // 添加消息到页面
    function addMessage(contentEl, isSelf=false){
        const msgList=document.getElementById('messages');
        const wrapper=document.createElement('div');
        wrapper.className='message '+(isSelf?'self':'other');
        wrapper.appendChild(contentEl);
        msgList.appendChild(wrapper);
        msgList.scrollTop=msgList.scrollHeight;
        return wrapper;
    }

    // 连接按钮
    document.getElementById('connectBtn').addEventListener('click', async ()=>{
        const inputId=document.getElementById('useridInput').value.trim();
        if(!inputId) return alert('请输入UserID');
        userid=inputId;
        document.getElementById('receiverArea').style.display='flex';
        document.getElementById('status').innerText='连接中...';

        client = new WsClient('ws://127.0.0.1:9500', userid, (msg)=>{
            const contentEl = renderMessageContent(msg);
            addMessage(contentEl, msg.sender === userid);
        });

        try{
            await client.connect();
            document.getElementById('status').innerText='已连接';
        }catch(e){
            document.getElementById('status').innerText='连接失败';
            console.error(e);
        }
    });

    // 发送按钮
    document.getElementById('sendBtn').addEventListener('click', async ()=>{
        if(!client) return alert('请先连接WebSocket');

        let receiverInput=document.getElementById('receiver').value.trim();
        let receiver;
        try{
            const parsed = JSON.parse(receiverInput);
            receiver = Array.isArray(parsed) ? parsed : receiverInput;
        }catch{
            receiver = receiverInput;
        }

        const payloadInput=document.getElementById('payload').value.trim();
        const fileInput=document.getElementById('fileInput');
        const file=fileInput.files[0];

        if(file){
            const contentEl=document.createElement('div');
            const nameDiv=document.createElement('div'); nameDiv.textContent=file.name; contentEl.appendChild(nameDiv);
            const progressWrapper=document.createElement('div'); progressWrapper.className='progress-bar';
            const progressInner=document.createElement('div'); progressInner.className='progress-bar-inner';
            progressWrapper.appendChild(progressInner); contentEl.appendChild(progressWrapper);
            const wrapper = addMessage(contentEl,true);

            await client.sendMessage(receiver, file, 1, null, 1024*1024, (i,total)=>{
                progressInner.style.width = `${Math.round(i/total*100)}%`;
            });

            // 上传完成替换展示
            const displayEl = renderMessageContent({type: file.type.startsWith('image/') ? 2 : file.type.startsWith('video/') ? 4 : file.type.startsWith('audio/') ? 6 : 3, payload: file, files:[{name:file.name}]});
            wrapper.innerHTML=''; wrapper.appendChild(displayEl);
            fileInput.value='';
        } else if(payloadInput){
            const contentEl=document.createElement('div'); contentEl.textContent=payloadInput;
            addMessage(contentEl,true);
            await client.sendMessage(receiver, payloadInput);
            document.getElementById('payload').value='';
        }
    });
</script>

</body>
</html>
