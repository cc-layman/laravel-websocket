<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>WebSocket 聊天（微信风格）</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #ece5dd;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* 主容器 */
        #chatBox {
            width: 800px;
            height: 600px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* 顶部连接区 */
        #connectArea {
            padding: 10px 20px;
            background-color: #075e54;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #connectArea input {
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            flex: 1;
        }
        #connectArea button {
            background: #25d366;
            border: none;
            color: #fff;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        #connectArea button:hover {
            background: #1ebe5a;
        }

        /* 接收方区 */
        #receiverArea {
            padding: 8px 20px;
            background-color: #f0f0f0;
            display: none;
            gap: 10px;
            align-items: center;
        }
        #receiverArea input {
            flex: 1;
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        /* 消息区 */
        #messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #e5ddd5;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .message {
            max-width: 60%;
            padding: 10px 14px;
            border-radius: 7px;
            word-wrap: break-word;
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.4;
        }
        .message.self {
            background-color: #dcf8c6;
            align-self: flex-end;
        }
        .message.other {
            background-color: #fff;
            border: 1px solid #ddd;
            align-self: flex-start;
        }
        .message img, .message video {
            display: block;
            margin-top: 5px;
            max-width: 200px;
            border-radius: 5px;
        }
        .message video { max-width: 250px; }

        /* 输入区 */
        #inputArea {
            display: flex;
            gap: 10px;
            padding: 10px 20px;
            border-top: 1px solid #ccc;
            background-color: #f7f7f7;
        }
        #inputArea input[type="text"] {
            flex: 1;
            padding: 7px 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        #inputArea input[type="file"] { border: none; }
        #inputArea button {
            padding: 7px 15px;
            border-radius: 5px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        #inputArea button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div id="chatBox">
    <!-- 顶部连接区 -->
    <div id="connectArea">
        <input type="text" id="useridInput" placeholder="输入你的UserID">
        <button id="connectBtn">连接</button>
        <span id="status">未连接</span>
    </div>

    <!-- 接收方区，连接成功后显示 -->
    <div id="receiverArea">
        <label>发送给:</label>
        <input type="text" id="receiver" placeholder="接收方用户ID/数组/null">
    </div>

    <!-- 消息区 -->
    <div id="messages"></div>

    <!-- 底部输入区 -->
    <div id="inputArea">
        <input type="text" id="payload" placeholder="输入消息">
        <input type="file" id="fileInput" accept="*/*">
        <button id="sendBtn">发送</button>
    </div>
</div>

<script>
    var wsBinaryUtils = (function(){
        function uuid() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                const r = Math.random()*16|0;
                const v = c==='x'? r : (r&0x3|0x8);
                return v.toString(16);
            });
        }

        function pack(type,sn,index,count,peer,payload){
            const peerBytes = new TextEncoder().encode(JSON.stringify(peer));
            const peerLength = peerBytes.length;
            const snHex = sn.replace(/-/g,'');
            const snBytes = new Uint8Array(16);
            for(let i=0;i<16;i++) snBytes[i]=parseInt(snHex.slice(i*2,i*2+2),16);
            let payloadBytes = payload instanceof ArrayBuffer ? new Uint8Array(payload) : new TextEncoder().encode(payload);
            const buffer = new Uint8Array(1+16+2+2+2+peerLength+payloadBytes.length);
            let offset=0;
            buffer[offset]=type; offset+=1;
            buffer.set(snBytes,offset); offset+=16;
            buffer.set([(index>>8)&0xff,index&0xff],offset); offset+=2;
            buffer.set([(count>>8)&0xff,count&0xff],offset); offset+=2;
            buffer.set([(peerLength>>8)&0xff,peerLength&0xff],offset); offset+=2;
            buffer.set(peerBytes,offset); offset+=peerLength;
            buffer.set(payloadBytes,offset);
            return buffer.buffer;
        }

        function unpack(arrayBuffer){
            const dv=new DataView(arrayBuffer); let offset=0;
            const type=dv.getUint8(offset); offset+=1;
            const snBytes=new Uint8Array(arrayBuffer,offset,16); offset+=16;
            const snHex=Array.from(snBytes).map(b=>b.toString(16).padStart(2,'0')).join('');
            const sn=snHex.slice(0,8)+'-'+snHex.slice(8,12)+'-'+snHex.slice(12,16)+'-'+snHex.slice(16,20)+'-'+snHex.slice(20,32);
            const index=dv.getUint16(offset); offset+=2;
            const count=dv.getUint16(offset); offset+=2;
            const peerLength=dv.getUint16(offset); offset+=2;
            const peerBytes=new Uint8Array(arrayBuffer,offset,peerLength);
            const peer=JSON.parse(new TextDecoder().decode(peerBytes));
            offset+=peerLength;
            const payloadBytes=new Uint8Array(arrayBuffer,offset);
            return {type,sn,index,count,peer,payload:payloadBytes};
        }
        return {uuid,pack,unpack};
    })();

    let ws=null, userid=null;

    function addMessage(contentEl,self=false){
        const msgList=document.getElementById('messages');
        const wrapper=document.createElement('div');
        wrapper.className='message '+(self?'self':'other');
        wrapper.appendChild(contentEl);
        msgList.appendChild(wrapper);
        msgList.scrollTop=msgList.scrollHeight;
    }

    document.getElementById('connectBtn').addEventListener('click',()=>{
        const inputId=document.getElementById('useridInput').value.trim();
        if(!inputId) return alert('请输入UserID');
        userid=inputId;

        // 显示接收方输入框
        document.getElementById('receiverArea').style.display='flex';
        document.getElementById('status').innerText='连接中...';

        ws=new WebSocket('ws://127.0.0.1:9500?userid='+encodeURIComponent(userid));
        ws.binaryType='arraybuffer';

        ws.onopen=()=>{document.getElementById('status').innerText='已连接';}
        ws.onclose=()=>{document.getElementById('status').innerText='已关闭';}
        ws.onerror=e=>console.error(e);

        ws.onmessage=event=>{
            const msg=wsBinaryUtils.unpack(event.data);
            if(msg.type===101){
                const pongPeer={sender:userid,receiver:'server',group_code:null,notice_type:1,files:null};
                ws.send(wsBinaryUtils.pack(102,msg.sn,1,1,pongPeer,'pong'));
                return;
            }
            let contentEl=document.createElement('div');
            if(msg.type===1){ contentEl.textContent=new TextDecoder().decode(msg.payload);}
            else if(msg.type===2){
                const blob=new Blob([msg.payload],{type:msg.peer.files[0]?.mime||'image/png'});
                const img=document.createElement('img'); img.src=URL.createObjectURL(blob);
                contentEl.appendChild(img);
            }else if(msg.type===3){
                const blob=new Blob([msg.payload],{type:msg.peer.files[0]?.mime||'application/octet-stream'});
                const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download=msg.peer.files[0]?.name||'download_file';
                a.textContent=`点击下载: ${a.download}`; contentEl.appendChild(a);
            }else if(msg.type===4){
                const blob=new Blob([msg.payload],{type:msg.peer.files[0]?.mime||'video/mp4'});
                const video=document.createElement('video'); video.src=URL.createObjectURL(blob); video.controls=true;
                contentEl.appendChild(video);
            }
            addMessage(contentEl,msg.peer.sender===userid);
        }
    });

    document.getElementById('sendBtn').addEventListener('click',()=>{
        if(!ws || ws.readyState!==WebSocket.OPEN) return alert('请先连接WebSocket');
        const receiverInput=document.getElementById('receiver').value.trim();
        const payloadInput=document.getElementById('payload').value.trim();
        let receiver=receiverInput; try{const parsed=JSON.parse(receiverInput); if(Array.isArray(parsed)) receiver=parsed;}catch{}
        const peer={sender:userid,receiver,group_code:null,notice_type:1,files:null};
        const sn=wsBinaryUtils.uuid();
        const fileInput=document.getElementById('fileInput');
        const file=fileInput.files[0];

        if(file){
            const reader=new FileReader();
            reader.onload=()=>{
                const arrayBuffer=reader.result;
                let type=3;
                if(file.type.startsWith('image/')) type=2;
                else if(file.type.startsWith('video/')) type=4;

                peer.files=[{name:file.name,size:file.size,mime:file.type}];
                ws.send(wsBinaryUtils.pack(type,sn,1,1,peer,arrayBuffer));

                let contentEl=document.createElement('div');
                if(type===2){const img=document.createElement('img'); img.src=URL.createObjectURL(file); contentEl.appendChild(img);}
                else if(type===3){const a=document.createElement('a'); a.href=URL.createObjectURL(file); a.download=file.name; a.textContent=`点击下载: ${file.name}`; contentEl.appendChild(a);}
                else if(type===4){const video=document.createElement('video'); video.src=URL.createObjectURL(file); video.controls=true; contentEl.appendChild(video);}
                addMessage(contentEl,true);
            }
            reader.readAsArrayBuffer(file);
        }else if(payloadInput){
            ws.send(wsBinaryUtils.pack(1,sn,1,1,peer,payloadInput));
            const contentEl=document.createElement('div');
            contentEl.textContent=payloadInput;
            addMessage(contentEl,true);
        }else alert('请填写文本消息或选择文件/图片/视频');
    });
</script>
</body>
</html>
