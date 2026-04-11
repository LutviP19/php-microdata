package main

import "C"
import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"sync"
	"time"
)

// Struktur input dari PHP
type RequestConfig struct {
	Method  string            `json:"method"`
	URL     string            `json:"url"`
	Headers map[string]string `json:"headers"`
	Body    string            `json:"body"`
	Timeout int               `json:"timeout"`
}

type MultiRequestConfig struct {
	Requests []RequestConfig `json:"requests"`
}

// Struktur output ke PHP
type ResponseData struct {
	Status int    `json:"status"`
	Body   string `json:"body"`
	Error  string `json:"error"`
}

// Helper untuk membuat response JSON C-String
func createJsonResponse(data interface{}) *C.char {
	jsonRes, _ := json.Marshal(data)
	return C.CString(string(jsonRes))
}

//export ExecuteRequest
func ExecuteRequest(jsonInput *C.char) *C.char {
	var cfg RequestConfig
	if err := json.Unmarshal([]byte(C.GoString(jsonInput)), &cfg); err != nil {
		return createJsonResponse(ResponseData{Error: "Invalid JSON Input"})
	}

	result := performRequest(cfg)
	return createJsonResponse(result)
}

//export ExecuteMultiRequest
func ExecuteMultiRequest(jsonInput *C.char) *C.char {
	var config MultiRequestConfig
	if err := json.Unmarshal([]byte(C.GoString(jsonInput)), &config); err != nil {
		return createJsonResponse([]ResponseData{{Error: "Invalid JSON Input"}})
	}

	results := make([]ResponseData, len(config.Requests))
	var wg sync.WaitGroup

	for i, reqCfg := range config.Requests {
		wg.Add(1)
		go func(index int, c RequestConfig) {
			defer wg.Done()
			results[index] = performRequest(c)
		}(i, reqCfg)
	}

	wg.Wait()
	return createJsonResponse(results)
}

// Core function dengan error handling yang lebih detail
func performRequest(cfg RequestConfig) ResponseData {
	if cfg.Timeout <= 0 {
		cfg.Timeout = 30
	}

	client := &http.Client{
		Timeout: time.Duration(cfg.Timeout) * time.Second,
	}

	req, err := http.NewRequest(cfg.Method, cfg.URL, bytes.NewBuffer([]byte(cfg.Body)))
	if err != nil {
		return ResponseData{Error: fmt.Sprintf("[Go Engine] Request Setup Error: %v", err)}
	}

	for k, v := range cfg.Headers {
		req.Header.Set(k, v)
	}

	resp, err := client.Do(req)
	if err != nil {
		// Menangkap timeout atau DNS failure
		return ResponseData{Error: fmt.Sprintf("[Go Engine] Connection Error: %v", err)}
	}
	defer resp.Body.Close()

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return ResponseData{Status: resp.StatusCode, Error: fmt.Sprintf("[Go Engine] Read Body Error: %v", err)}
	}

	return ResponseData{
		Status: resp.StatusCode,
		Body:   string(body),
	}
}

func main() {}
