export class WsClient {
    static instance = null;

    constructor(url, userId, onMessage) {
        if (WsClient.instance) {
            if (url) WsClient.instance.url = url;
            if (userId) WsClient.instance.userId = userId;
            if (onMessage) WsClient.instance.onMessage = onMessage;
            return WsClient.instance;
        }

        this.url = url;
        this.userId = userId;
        this.onMessage = onMessage;
        this.ws = null;
        this._fragmentCache = {};
        this._isManuallyClosed = false;

        // 重连配置
        this._reconnectCount = 0;
        this._maxReconnect = 10;
        this._baseReconnectDelay = 1000;

        WsClient.instance = this;
    }

    /** 生成 UUID */
    static generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /** 打包消息 */
    static pack(type, sn, index, count, peer, payload) {
        const safePeer = peer || {};
        if (!safePeer.files) safePeer.files = [];

        const peerJson = JSON.stringify(safePeer);
        const peerBytes = new TextEncoder().encode(peerJson);
        const peerLength = peerBytes.length;
        if (peerLength > 65535) throw new Error('Peer 数据过大');

        const snHex = sn.replace(/-/g, '');
        const snBytes = new Uint8Array(16);
        for (let i = 0; i < 16; i++) snBytes[i] = parseInt(snHex.slice(i * 2, i * 2 + 2), 16);

        let payloadBytes;
        if (payload instanceof ArrayBuffer) payloadBytes = new Uint8Array(payload);
        else if (payload instanceof Uint8Array) payloadBytes = payload;
        else payloadBytes = new TextEncoder().encode(payload);

        const buffer = new Uint8Array(1 + 16 + 2 + 2 + 2 + peerLength + payloadBytes.length);
        let offset = 0;
        buffer[offset++] = type;
        buffer.set(snBytes, offset); offset += 16;
        buffer.set([(index >> 8) & 0xff, index & 0xff], offset); offset += 2;
        buffer.set([(count >> 8) & 0xff, count & 0xff], offset); offset += 2;
        buffer.set([(peerLength >> 8) & 0xff, peerLength & 0xff], offset); offset += 2;
        buffer.set(peerBytes, offset); offset += peerLength;
        buffer.set(payloadBytes, offset);

        return buffer.buffer;
    }

    /** 解包消息 */
    static unpack(arrayBuffer) {
        const dataView = new DataView(arrayBuffer);
        let offset = 0;

        const type = dataView.getUint8(offset); offset += 1;
        const snBytes = new Uint8Array(arrayBuffer, offset, 16); offset += 16;
        const snHex = Array.from(snBytes).map(b => b.toString(16).padStart(2, '0')).join('');
        const sn = `${snHex.slice(0, 8)}-${snHex.slice(8, 12)}-${snHex.slice(12, 16)}-${snHex.slice(16, 20)}-${snHex.slice(20, 32)}`;

        const index = dataView.getUint16(offset); offset += 2;
        const count = dataView.getUint16(offset); offset += 2;
        const peerLength = dataView.getUint16(offset); offset += 2;

        const peerBytes = new Uint8Array(arrayBuffer, offset, peerLength);
        let peer = {};
        try { peer = JSON.parse(new TextDecoder().decode(peerBytes)) || {}; } catch {}
        offset += peerLength;

        const payloadBytes = new Uint8Array(arrayBuffer.slice(offset));

        return {
            type,
            sn,
            index,
            count,
            noticeType: peer.notice_type ?? null,
            sender: peer.sender ?? null,
            receiver: peer.receiver ?? null,
            groupCode: peer.group_code ?? null,
            files: Array.isArray(peer.files) ? peer.files : [],
            payload: payloadBytes
        };
    }

    /** 连接 WebSocket */
    connect() {
        this._isManuallyClosed = false;
        return new Promise((resolve, reject) => this._connect(resolve, reject));
    }

    _connect(resolve, reject) {
        if (this.ws) this.ws.close();

        this.ws = new WebSocket(`${this.url}?userid=${encodeURIComponent(this.userId)}`);
        this.ws.binaryType = 'arraybuffer';

        this.ws.onopen = () => {
            console.log('✅ WebSocket 已连接');
            this._reconnectCount = 0;
            resolve?.();
        };

        this.ws.onclose = () => {
            console.warn('❌ WebSocket 已关闭');
            if (!this._isManuallyClosed) this._reconnect();
        };

        this.ws.onerror = e => {
            console.error('WebSocket 错误', e);
            reject?.(e);
        };

        this.ws.onmessage = event => {
            const msg = WsClient.unpack(event.data);
            if (msg.type === 101) { // ping
                const pongPeer = { sender: this.userId, receiver: 'server', group_code: null, notice_type: 1, files: [] };
                this.ws.send(WsClient.pack(102, msg.sn, 1, 1, pongPeer, 'pong'));
                return;
            }
            this._handleMessage(msg);
        };
    }

    /** 关闭连接 */
    close() {
        this._isManuallyClosed = true;
        this.ws?.close();
    }

    /** 自动重连 */
    _reconnect() {
        if (this._reconnectCount >= this._maxReconnect) return console.warn('⚠️ 最大重连次数');
        const delay = this._baseReconnectDelay * Math.pow(2, this._reconnectCount);
        setTimeout(() => {
            this._reconnectCount++;
            this._connect();
        }, delay);
    }

    /** 处理消息（分片和普通消息） */
    _handleMessage(msg) {
        const safeFiles = Array.isArray(msg.files) ? msg.files : [];

        if (msg.count > 1) {
            // 分片消息
            if (!this._fragmentCache[msg.sn]) {
                this._fragmentCache[msg.sn] = {
                    chunks: new Map(),
                    total: msg.count,
                    peer: { sender: msg.sender, receiver: msg.receiver, files: safeFiles },
                    type: msg.type
                };
                // 5分钟后清理缓存
                setTimeout(() => delete this._fragmentCache[msg.sn], 5 * 60 * 1000);
            }

            this._fragmentCache[msg.sn].chunks.set(msg.index - 1, msg.payload);

            if (this._fragmentCache[msg.sn].chunks.size === msg.count) {
                const orderedChunks = [];
                for (let i = 0; i < msg.count; i++) orderedChunks.push(this._fragmentCache[msg.sn].chunks.get(i));
                const blob = new Blob(orderedChunks, { type: safeFiles[0]?.mime || undefined });
                this.onMessage?.({ ...this._fragmentCache[msg.sn], payload: blob, files: this._fragmentCache[msg.sn].peer.files });
                delete this._fragmentCache[msg.sn];
            }
        } else {
            // 普通消息
            let payload = null;
            try {
                const textTypes = [1, 5, 7, 8, 9, 101, 102];
                if (textTypes.includes(msg.type)) payload = new TextDecoder().decode(msg.payload);
                else payload = new Blob([msg.payload], { type: safeFiles[0]?.mime || undefined });
            } catch {
                payload = msg.payload;
            }

            this.onMessage?.({ ...msg, payload, files: safeFiles });
        }
    }

    /** 发送消息（支持文件分片） */
    async sendMessage(receiver, content, noticeType = 1, groupCode = null, chunkSize = 2 * 1024 * 1024, progressCb = null) {
        const sn = WsClient.generateUUID();
        const peer = { sender: this.userId, receiver, group_code: groupCode, notice_type: noticeType, files: null };

        if (content instanceof File) {
            const typeMap = { image: 2, video: 4, audio: 6 };
            const type = typeMap[content.type.split('/')[0]] || 3;
            const files = [{ name: content.name, size: content.size, mime: content.type }];
            const arrayBuffer = await content.arrayBuffer();
            const totalChunks = Math.ceil(arrayBuffer.byteLength / chunkSize);

            for (let i = 0; i < totalChunks; i++) {
                const start = i * chunkSize, end = Math.min(start + chunkSize, arrayBuffer.byteLength);
                const chunk = arrayBuffer.slice(start, end);
                const buffer = WsClient.pack(type, sn, i + 1, totalChunks, { ...peer, files }, chunk);
                await this._sendBuffer(buffer);
                progressCb?.(i + 1, totalChunks);
            }
            return sn;
        }

        // 文本/JSON 消息
        const payloadStr = typeof content === 'string' ? content : JSON.stringify(content);
        const buffer = WsClient.pack(1, sn, 1, 1, peer, payloadStr);
        await this._sendBuffer(buffer);
        return sn;
    }

    /** 确保顺序发送 */
    _sendBuffer(buffer) {
        return new Promise((resolve, reject) => {
            const trySend = () => {
                if (this.ws?.readyState === 1) {
                    try { this.ws.send(buffer); resolve(); } catch (e) { reject(e); }
                } else setTimeout(trySend, 50);
            };
            trySend();
        });
    }
}
