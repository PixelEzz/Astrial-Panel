package main

import (
	"bufio"
	"encoding/json"
	"fmt"
	"log"
	"net"
	"net/http"
	"strings"
	"sync"
	"time"
)

type EmailMessage struct {
	ID        string            `json:"ID"`
	From      EmailAddress      `json:"From"`
	To        []EmailAddress    `json:"To"`
	Subject   string            `json:"Subject"`
	Date      time.Time         `json:"Date"`
	Snippet   string            `json:"Snippet"`
	HTML      string            `json:"HTML"`
	Text      string            `json:"Text"`
	Headers   map[string]string `json:"Headers"`
}

type EmailAddress struct {
	Name    string `json:"Name"`
	Address string `json:"Address"`
}

type MessagesResponse struct {
	Total    int            `json:"total"`
	Messages []EmailMessage `json:"messages"`
}

var (
	mu       sync.RWMutex
	messages []EmailMessage
)

func main() {
	go startSMTPServer(":1025")
	startHTTPServer(":8025")
}

func startSMTPServer(addr string) {
	ln, err := net.Listen("tcp", addr)
	if err != nil {
		log.Fatalf("SMTP listen failed: %v", err)
	}
	log.Printf("Mailpit SMTP server listening on %s", addr)

	for {
		conn, err := ln.Accept()
		if err != nil {
			continue
		}
		go handleSMTP(conn)
	}
}

func handleSMTP(conn net.Conn) {
	defer conn.Close()
	reader := bufio.NewReader(conn)
	writer := bufio.NewWriter(conn)

	fmt.Fprintf(writer, "220 mailpit.local ESMTP ready\r\n")
	writer.Flush()

	var from string
	var to []string
	var dataMode bool
	var dataLines []string

	for {
		line, err := reader.ReadString('\n')
		if err != nil {
			break
		}
		cleanLine := strings.TrimRight(line, "\r\n")

		if dataMode {
			if cleanLine == "." {
				dataMode = false
				saveMessage(from, to, dataLines)
				fmt.Fprintf(writer, "250 2.0.0 Ok: queued\r\n")
				writer.Flush()
			} else {
				dataLines = append(dataLines, cleanLine)
			}
			continue
		}

		upper := strings.ToUpper(cleanLine)
		if strings.HasPrefix(upper, "HELO") || strings.HasPrefix(upper, "EHLO") {
			fmt.Fprintf(writer, "250-mailpit.local\r\n250-PIPELINING\r\n250-8BITMIME\r\n250 SMTPUTF8\r\n")
			writer.Flush()
		} else if strings.HasPrefix(upper, "MAIL FROM:") {
			from = strings.Trim(cleanLine[10:], " <>")
			fmt.Fprintf(writer, "250 2.1.0 Ok\r\n")
			writer.Flush()
		} else if strings.HasPrefix(upper, "RCPT TO:") {
			to = append(to, strings.Trim(cleanLine[8:], " <>"))
			fmt.Fprintf(writer, "250 2.1.5 Ok\r\n")
			writer.Flush()
		} else if upper == "DATA" {
			dataMode = true
			dataLines = nil
			fmt.Fprintf(writer, "354 End data with <CR><LF>.<CR><LF>\r\n")
			writer.Flush()
		} else if upper == "QUIT" {
			fmt.Fprintf(writer, "221 2.0.0 Bye\r\n")
			writer.Flush()
			break
		} else if upper == "RSET" {
			from = ""
			to = nil
			fmt.Fprintf(writer, "250 2.0.0 Ok\r\n")
			writer.Flush()
		} else if upper == "NOOP" {
			fmt.Fprintf(writer, "250 2.0.0 Ok\r\n")
			writer.Flush()
		} else {
			fmt.Fprintf(writer, "250 Ok\r\n")
			writer.Flush()
		}
	}
}

func saveMessage(from string, to []string, lines []string) {
	headers := make(map[string]string)
	var bodyLines []string
	inHeaders := true

	for _, l := range lines {
		if inHeaders {
			if l == "" {
				inHeaders = false
				continue
			}
			parts := strings.SplitN(l, ":", 2)
			if len(parts) == 2 {
				headers[strings.TrimSpace(parts[0])] = strings.TrimSpace(parts[1])
			}
		} else {
			bodyLines = append(bodyLines, l)
		}
	}

	body := strings.Join(bodyLines, "\n")
	subject := headers["Subject"]
	if subject == "" {
		subject = "No Subject"
	}

	msg := EmailMessage{
		ID:        fmt.Sprintf("msg_%d", time.Now().UnixNano()),
		From:      EmailAddress{Address: from},
		Date:      time.Now(),
		Subject:   subject,
		HTML:      body,
		Text:      body,
		Snippet:   body,
		Headers:   headers,
	}
	if len(msg.Snippet) > 100 {
		msg.Snippet = msg.Snippet[:100] + "..."
	}
	for _, t := range to {
		msg.To = append(msg.To, EmailAddress{Address: t})
	}

	mu.Lock()
	messages = append([]EmailMessage{msg}, messages...)
	mu.Unlock()
	log.Printf("[Mailpit] Received message from %s: '%s' to %v", from, subject, to)
}

func startHTTPServer(addr string) {
	mux := http.NewServeMux()
	mux.HandleFunc("/api/v1/messages", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		w.Header().Set("Access-Control-Allow-Origin", "*")
		if r.Method == "DELETE" {
			mu.Lock()
			messages = nil
			mu.Unlock()
			json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
			return
		}
		mu.RLock()
		resp := MessagesResponse{
			Total:    len(messages),
			Messages: messages,
		}
		mu.RUnlock()
		json.NewEncoder(w).Encode(resp)
	})

	mux.HandleFunc("/api/v1/message/", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		w.Header().Set("Access-Control-Allow-Origin", "*")
		id := strings.TrimPrefix(r.URL.Path, "/api/v1/message/")
		mu.RLock()
		defer mu.RUnlock()
		for _, m := range messages {
			if m.ID == id {
				json.NewEncoder(w).Encode(m)
				return
			}
		}
		http.Error(w, `{"error":"not found"}`, 404)
	})

	mux.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "text/html")
		mu.RLock()
		defer mu.RUnlock()
		html := "<html><head><title>Mailpit Mock</title><style>body{font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:20px;}</style></head><body><h1>Mailpit Local Inbox</h1><ul>"
		for _, m := range messages {
			html += fmt.Sprintf("<li><b>%s</b> &lt;%s&gt; - <i>%s</i><br><pre>%s</pre></li>", m.Subject, m.From.Address, m.Date.Format(time.RFC3339), m.Snippet)
		}
		html += "</ul></body></html>"
		w.Write([]byte(html))
	})

	log.Printf("Mailpit Web & API server listening on %s", addr)
	if err := http.ListenAndServe(addr, mux); err != nil {
		log.Fatalf("HTTP listen failed: %v", err)
	}
}
