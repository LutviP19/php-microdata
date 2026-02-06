package main

/*
#include <stdlib.h>
*/
import "C" // Required for C-style exports
import (
	"bufio"
	"fmt"
	"net/http"
	"os"
	"strings"
	"sync"
	"unsafe"
	"github.com/PuerkitoBio/goquery"
)

//export crawler
func crawler(filePath *C.char) *C.char {
    // Convert C string to Go string
    path := C.GoString(filePath)

	urlFile, err := os.Open(path)
	if err != nil {
		return C.CString("Error: Could not open " + path)
	}
	defer urlFile.Close()

	var wg sync.WaitGroup
	var mu sync.Mutex
	var results []string
	scanner := bufio.NewScanner(urlFile)
	for scanner.Scan() {
		url := scanner.Text()
		if url == "" { continue }

		wg.Add(1)
		// Launch a Goroutine for each URL
		go func(target string) {
			defer wg.Done()
			
			resp, err := http.Get(target)
			status := ""
			if err != nil {
				status = fmt.Sprintf("FAILED (%v)", err)

				// Safely append to the results slice
				mu.Lock()
				results = append(results, fmt.Sprintf("%s: %s", target, status))
				mu.Unlock()
			} else {
				// status = fmt.Sprintf("SUCCESS (%d)", resp.StatusCode)

				// Only parse if the response code is 200 OK
				if resp.StatusCode == http.StatusOK {

					results = append(results, fmt.Sprintf("%s: %d", url, http.StatusOK))

					doc, err := goquery.NewDocumentFromReader(resp.Body)
					if err != nil {
						mu.Lock()
						results = append(results, fmt.Sprintf("Error parsing %s: %v", target, err))
						mu.Unlock()
						return
					}

					// Example: Extract and save the <title> tag text
					title := doc.Find("title").Text()
					// body := doc.Find("body").Text()

					// Safety: Always lock when appending in a Goroutine
					mu.Lock()
					results = append(results, fmt.Sprintf("URL: %s | Title: %s", target, title))
					// results = append(results, fmt.Sprintf("URL: %s | Body: %s", target, body))
					mu.Unlock()

					// Select a specific tag with a specific class (e.g., <div class="article-title">)
					doc.Find("article.card").Each(func(i int, s *goquery.Selection) {
						content := s.Text()
						link, _ := s.Find("a").Attr("href")

						mu.Lock()
						results = append(results, fmt.Sprintf("  - Article Content: %s", content))
						if link != "" {
							results = append(results, fmt.Sprintf("  - Link: %s", link))
						}
						mu.Unlock()
					})
				} else {
					mu.Lock()
					results = append(results, fmt.Sprintf("%s: %d", target, resp.StatusCode))
					mu.Unlock()
				}

				mu.Lock()
				results = append(results, fmt.Sprint("===========================END URL============================\n"))
				mu.Unlock()
				resp.Body.Close()
			}
		}(url)
	}

	wg.Wait() // Wait for all crawlers to finish
	return C.CString(strings.Join(results, "\n"))
}

//export FreeString
func FreeString(ptr *C.char) {
    if ptr != nil {
        C.free(unsafe.Pointer(ptr))
    }
}

func main() {}
