package main

import (
	"bufio"
	"crypto/sha1"
	"encoding/base64"
	"encoding/binary"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/url"
	"strings"
	"sync"
)

type WSConn struct {
	conn net.Conn
	rw   *bufio.ReadWriter
	mu   sync.Mutex
}

func UpgradeWS(w http.ResponseWriter, r *http.Request) (*WSConn, error) {
	key := r.Header.Get("Sec-WebSocket-Key")
	if key == "" {
		http.Error(w, "missing Sec-WebSocket-Key", 400)
		return nil, fmt.Errorf("missing key")
	}
	h := sha1.New()
	h.Write([]byte(key + "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"))
	accept := base64.StdEncoding.EncodeToString(h.Sum(nil))

	hj, ok := w.(http.Hijacker)
	if !ok {
		return nil, fmt.Errorf("cannot hijack connection")
	}
	conn, rw, err := hj.Hijack()
	if err != nil {
		return nil, err
	}

	res := fmt.Sprintf("HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: %s\r\n\r\n", accept)
	if _, err := rw.WriteString(res); err != nil {
		conn.Close()
		return nil, err
	}
	rw.Flush()
	return &WSConn{conn: conn, rw: rw}, nil
}

func (ws *WSConn) WriteText(msg string) error {
	ws.mu.Lock()
	defer ws.mu.Unlock()
	data := []byte(msg)
	l := len(data)
	var header []byte
	if l <= 125 {
		header = []byte{0x81, byte(l)}
	} else if l <= 65535 {
		header = []byte{0x81, 126, 0, 0}
		binary.BigEndian.PutUint16(header[2:], uint16(l))
	} else {
		header = make([]byte, 10)
		header[0] = 0x81
		header[1] = 127
		binary.BigEndian.PutUint64(header[2:], uint64(l))
	}
	if _, err := ws.rw.Write(header); err != nil {
		return err
	}
	if _, err := ws.rw.Write(data); err != nil {
		return err
	}
	return ws.rw.Flush()
}

func (ws *WSConn) ReadText() (string, error) {
	b0, err := ws.rw.ReadByte()
	if err != nil {
		return "", err
	}
	b1, err := ws.rw.ReadByte()
	if err != nil {
		return "", err
	}
	masked := (b1 & 0x80) != 0
	length := int(b1 & 0x7F)
	if length == 126 {
		var l uint16
		if err := binary.Read(ws.rw, binary.BigEndian, &l); err != nil {
			return "", err
		}
		length = int(l)
	} else if length == 127 {
		var l uint64
		if err := binary.Read(ws.rw, binary.BigEndian, &l); err != nil {
			return "", err
		}
		length = int(l)
	}
	var mask [4]byte
	if masked {
		if _, err := io.ReadFull(ws.rw, mask[:]); err != nil {
			return "", err
		}
	}
	payload := make([]byte, length)
	if _, err := io.ReadFull(ws.rw, payload); err != nil {
		return "", err
	}
	if masked {
		for i := 0; i < length; i++ {
			payload[i] ^= mask[i%4]
		}
	}
	opcode := b0 & 0x0F
	if opcode == 8 {
		return "", io.EOF
	}
	return string(payload), nil
}

func (ws *WSConn) Close() error {
	return ws.conn.Close()
}

func (app *App) proxyWebSocket(w http.ResponseWriter, r *http.Request, targetURL string) {
	clientWS, err := UpgradeWS(w, r)
	if err != nil {
		return
	}
	defer clientWS.Close()

	u, err := url.Parse(targetURL)
	if err != nil {
		return
	}

	host := u.Host
	if !strings.Contains(host, ":") {
		host += ":80"
	}

	daemonConn, err := net.Dial("tcp", host)
	if err != nil {
		clientWS.WriteText(fmt.Sprintf("\r\n[Error]: Unable to connect to daemon at %s\r\n", host))
		return
	}
	defer daemonConn.Close()

	key := "x3JJHMbDL1EzLkh9GBhXDw=="
	req := fmt.Sprintf("GET %s HTTP/1.1\r\nHost: %s\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: %s\r\nSec-WebSocket-Version: 13\r\n\r\n", u.RequestURI(), u.Host, key)
	daemonConn.Write([]byte(req))

	daemonReader := bufio.NewReader(daemonConn)
	for {
		line, err := daemonReader.ReadString('\n')
		if err != nil {
			return
		}
		if strings.TrimSpace(line) == "" {
			break
		}
	}
	daemonWS := &WSConn{conn: daemonConn, rw: bufio.NewReadWriter(daemonReader, bufio.NewWriter(daemonConn))}

	// Goroutine 1: Daemon -> Client
	go func() {
		for {
			msg, err := daemonWS.ReadText()
			if err != nil {
				break
			}
			if err := clientWS.WriteText(msg); err != nil {
				break
			}
		}
	}()

	// Goroutine 2: Client -> Daemon
	for {
		msg, err := clientWS.ReadText()
		if err != nil {
			break
		}
		// Send masked to daemon
		payload := []byte(msg)
		mask := [4]byte{1, 2, 3, 4}
		masked := make([]byte, len(payload))
		for i := range payload {
			masked[i] = payload[i] ^ mask[i%4]
		}
		frame := []byte{0x81, byte(0x80 | len(payload)), mask[0], mask[1], mask[2], mask[3]}
		frame = append(frame, masked...)
		daemonConn.Write(frame)
	}
}
