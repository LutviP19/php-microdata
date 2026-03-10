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

	var results []string
	scanner := bufio.NewScanner(urlFile)
	for scanner.Scan() {
		url := scanner.Text()
		resp, err := http.Get(url)
		if err != nil {
			results = append(results, fmt.Sprintf("%s: Error - %v", url, err))
			continue
		}
		defer resp.Body.Close()

		// Only parse if the response code is 200 OK
		if resp.StatusCode == http.StatusOK {

			results = append(results, fmt.Sprintf("%s: %d", url, http.StatusOK))

			doc, err := goquery.NewDocumentFromReader(resp.Body)
			if err != nil {
				fmt.Printf("Error parsing %s: %v\n", url, err)
				results = append(results, fmt.Sprintf("Error parsing %s: %v\n", url, err))
				continue
			}

			// Example: Extract and save the <title> tag text
			title := doc.Find("title").Text()
			// body := doc.Find("body").Text()


			// 2. Select a specific tag with a specific class (e.g., <div class="article-title">)
			doc.Find("article.card").Each(func(i int, s *goquery.Selection) {
			    // 3. Extract the text within the tag
			    content := s.Text()

				// URL and Title
				results = append(results, fmt.Sprintf("URL: %s | Title: %s\n", url, title))
				// Article Content
				results = append(results, fmt.Sprintf("Article Content: %s\n", content))
			    
			    // Optional: Get an attribute like a link
			    if link, exists := s.Find("a").Attr("href"); exists {
			        // fmt.Println("Link found:", link)
					results = append(results, fmt.Sprintf("Link found: %s\n", link))
			    }
			})
		} else {
			results = append(results, fmt.Sprintf("%s: %d", url, resp.StatusCode))
		}

		// Separator for each URL
		results = append(results, fmt.Sprint("===========================END URL============================\n"))
	}

	// 2. Join the slice into one multi-line string
	finalOutput := strings.Join(results, "\n")

	// 3. Convert Go string to C-string (Allocates memory!)
	return C.CString(finalOutput)
}

//export FreeString
func FreeString(ptr *C.char) {
    if ptr != nil {
        C.free(unsafe.Pointer(ptr))
    }
}

func main() {}
