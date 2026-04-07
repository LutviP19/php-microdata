package main

import "C"
import (
	"context"
	"time"

	"github.com/rabbitmq/amqp091-go"
)

//export Publish
func Publish(url, queueName, body *C.char) *C.char {
	conn, err := amqp091.Dial(C.GoString(url))
	if err != nil {
		return C.CString(err.Error())
	}
	defer conn.Close()

	ch, err := conn.Channel()
	if err != nil {
		return C.CString(err.Error())
	}
	defer ch.Close()

	q, _ := ch.QueueDeclare(C.GoString(queueName), true, false, false, false, nil)

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	err = ch.PublishWithContext(ctx, "", q.Name, false, false, amqp091.Publishing{
		ContentType: "text/plain",
		Body:        []byte(C.GoString(body)),
	})

	if err != nil {
		return C.CString(err.Error())
	}
	return nil
}

//export Consume
func Consume(url, queueName *C.char) *C.char {
	conn, err := amqp091.Dial(C.GoString(url))
	if err != nil {
		return C.CString("ERROR: " + err.Error())
	}
	defer conn.Close()

	ch, err := conn.Channel()
	if err != nil {
		return C.CString("ERROR: " + err.Error())
	}
	defer ch.Close()

	q, _ := ch.QueueDeclare(C.GoString(queueName), true, false, false, false, nil)

	msgs, err := ch.Consume(q.Name, "", true, false, false, false, nil)
	if err != nil {
		return C.CString("ERROR: " + err.Error())
	}

	// Ambil satu pesan saja lalu return ke PHP
	d := <-msgs
	return C.CString(string(d.Body))
}

func main() {}
