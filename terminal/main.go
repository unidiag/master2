package main

import (
	"encoding/json"
	"io"
	"log"
	"net/http"
	"os"
	"os/exec"

	"github.com/creack/pty"
	"github.com/gorilla/websocket"
)

type ClientMessage struct {
	Type string `json:"type"`
	Data string `json:"data"`
	Cols uint16 `json:"cols"`
	Rows uint16 `json:"rows"`
}

var upgrader = websocket.Upgrader{
	CheckOrigin: func(r *http.Request) bool {
		return r.Header.Get("Origin") ==
			"https://master2.trianda.by"
	},
}

func main() {
	http.HandleFunc(
		"/terminal-ws",
		handleTerminal,
	)

	log.Println(
		"terminal server listening on 127.0.0.1:8022",
	)

	log.Fatal(
		http.ListenAndServe(
			"127.0.0.1:8022",
			nil,
		),
	)
}

func handleTerminal(
	w http.ResponseWriter,
	r *http.Request,
) {
	if !authorized(r) {
		http.Error(
			w,
			"forbidden",
			http.StatusForbidden,
		)

		return
	}

	conn, err := upgrader.Upgrade(
		w,
		r,
		nil,
	)
	if err != nil {
		return
	}
	defer conn.Close()

	cmd := exec.Command("/bin/bash")
	cmd.Dir = "/"

	cmd.Env = append(
		os.Environ(),
		"TERM=xterm-256color",
	)

	ptmx, err := pty.StartWithSize(
		cmd,
		&pty.Winsize{
			Rows: 30,
			Cols: 120,
		},
	)
	if err != nil {
		log.Printf(
			"pty: %v",
			err,
		)

		return
	}
	defer func() {
		_ = ptmx.Close()
		_ = cmd.Process.Kill()
	}()

	done := make(chan struct{})

	go func() {
		defer close(done)

		buf := make(
			[]byte,
			32*1024,
		)

		for {
			n, err := ptmx.Read(buf)

			if n > 0 {
				err = conn.WriteMessage(
					websocket.BinaryMessage,
					buf[:n],
				)

				if err != nil {
					return
				}
			}

			if err != nil {
				if err != io.EOF {
					log.Printf(
						"pty read: %v",
						err,
					)
				}

				return
			}
		}
	}()

	for {
		_, data, err := conn.ReadMessage()

		if err != nil {
			break
		}

		var message ClientMessage

		if err := json.Unmarshal(
			data,
			&message,
		); err != nil {
			continue
		}

		switch message.Type {
		case "input":
			_, _ = ptmx.Write(
				[]byte(message.Data),
			)

		case "resize":
			if message.Rows > 0 && message.Cols > 0 {
				_ = pty.Setsize(
					ptmx,
					&pty.Winsize{
						Rows: message.Rows,
						Cols: message.Cols,
					},
				)
			}
		}
	}

	_ = ptmx.Close()

	<-done
}

func authorized(
	r *http.Request,
) bool {
	/*
		Здесь НЕ доверяем параметру типа ?user=admin.

		Проверка будет через короткоживущий
		одноразовый token, выданный самим master2.
	*/
	return true
}
