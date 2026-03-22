package main

/*
#include <stdlib.h>
#include <string.h>
*/
import "C"
import (
	"database/sql"
	"encoding/json"
	"fmt"
	"regexp"
	"strings"
	"unsafe"

	_ "modernc.org/sqlite"
)

// Data structure matching your PHP input
type Entry struct {
	Title   string `json:"title"`
	Content string `json:"content"`
	Tags    string `json:"tags"`
}

type ResultContainer struct {
	Message string
}

// Result structure
type SearchResult struct {
	ID      int64  `json:"id"`
	Title   string `json:"title"`
	Content string `json:"content"`
	Tags    string `json:"tags"`
}

//export SaveBulkData
func SaveBulkData(dbPath *C.char, jsonData *C.char) unsafe.Pointer {
	db, _ := sql.Open("sqlite", C.GoString(dbPath))
	defer db.Close()

	tx, _ := db.Begin()
	defer tx.Rollback()

	// 1. Prepare Check, Main, and FTS statements
	stmtCheck, _ := tx.Prepare("SELECT 1 FROM indexed_contents WHERE title = ? LIMIT 1")
	stmtMain, _ := tx.Prepare("INSERT INTO indexed_contents (title, content, tags) VALUES (?, ?, ?)")
	stmtFts, _ := tx.Prepare("INSERT INTO indexed_contents_fts (rowid, title, content, tags) VALUES (?, ?, ?, ?)")

	var entries []Entry
	json.Unmarshal([]byte(C.GoString(jsonData)), &entries)

	count := 0
	for _, e := range entries {
		// --- THE CHECK ---
		var exists int
		err := stmtCheck.QueryRow(e.Title).Scan(&exists)

		// If err is sql.ErrNoRows, it means the title is unique. Proceed!
		if err == sql.ErrNoRows {
			// Insert Main
			res, _ := stmtMain.Exec(e.Title, e.Content, e.Tags)
			newID, _ := res.LastInsertId()

			// Sync FTS
			stmtFts.Exec(newID, e.Title, e.Content, e.Tags)
			count++
		}
	}

	tx.Commit()
	return unsafe.Pointer(C.CString(fmt.Sprintf("Inserted %d new records", count)))
}

//export GetSize
func GetSize(ptr unsafe.Pointer) C.int {
	// Cast back to C string to get length
	return C.int(C.strlen((*C.char)(ptr)) + 1)
}

//export FillAndFree
func FillAndFree(ptr unsafe.Pointer, outBuffer *C.char) {
	cStr := (*C.char)(ptr)

	// Copy from C-Heap (Go side) to PHP-Buffer (PHP side)
	C.strcpy(outBuffer, cStr)

	// VERY IMPORTANT: Free the memory allocated by C.CString
	C.free(ptr)
}

//export SearchFTS
func SearchFTS(dbPath *C.char, query *C.char, limit C.int) *C.char {
	db, err := sql.Open("sqlite", C.GoString(dbPath))
	if err != nil {
		return C.CString(`[]`)
	}
	defer db.Close()

	// 1. Sanitize & Build FTS Query (Same as your PHP logic)
	// Remove non-alphanumeric except spaces
	reg := regexp.MustCompile(`[^A-Za-z0-9 ]`)
	clean := reg.ReplaceAllString(C.GoString(query), "")
	words := strings.Fields(strings.TrimSpace(clean))

	if len(words) == 0 {
		return C.CString(`[]`)
	}

	// Transform "word1 word2" -> "word1* AND word2*"
	var ftsParts []string
	for _, w := range words {
		ftsParts = append(ftsParts, w+"*")
	}
	ftsQuery := strings.Join(ftsParts, " AND ")

	// 2. Execute SQL with BM25 Ranking
	sqlStr := `
		SELECT m.id, m.title, m.content, m.tags 
		FROM indexed_contents m
		JOIN indexed_contents_fts f ON m.id = f.rowid
		WHERE indexed_contents_fts MATCH ? 
		ORDER BY bm25(indexed_contents_fts) ASC 
		LIMIT ?`

	rows, err := db.Query(sqlStr, ftsQuery, int(limit))
	if err != nil {
		return C.CString(`{"error":"` + err.Error() + `"}`)
	}
	defer rows.Close()

	// 3. Collect Results
	results := []SearchResult{}
	for rows.Next() {
		var r SearchResult
		// IMPORTANT: Scan must match the number of columns in SELECT (id, title, content, tags)
		err := rows.Scan(&r.ID, &r.Title, &r.Content, &r.Tags)
		if err != nil {
			// Return the error string so PHP can see why it failed
			return C.CString(`{"error":"Scan error: ` + err.Error() + `"}`)
		}
		results = append(results, r)
	}

	// If no results found, return an empty array string
	if len(results) == 0 {
		return C.CString(`[]`)
	}

	output, _ := json.Marshal(results)
	return C.CString(string(output))
}

//export FreeString
func FreeString(ptr unsafe.Pointer) {
	C.free(ptr)
}

//export UpdateRecord
func UpdateRecord(dbPath *C.char, id int64, content *C.char, tags *C.char) *C.char {
	db, _ := sql.Open("sqlite", C.GoString(dbPath))
	defer db.Close()
	tx, _ := db.Begin()

	// 1. Update Master Table
	_, err := tx.Exec("UPDATE indexed_contents SET content = ?, tags = ? WHERE id = ?", C.GoString(content), C.GoString(tags), id)

	// 2. Sync FTS5 (Delete old index, then Insert new one)
	tx.Exec("INSERT INTO indexed_contents_fts(indexed_contents_fts, rowid, content, tags) VALUES('delete', ?, ?, ?)", id, C.GoString(content), C.GoString(tags))
	tx.Exec("INSERT INTO indexed_contents_fts(rowid, content, tags) VALUES(?, ?, ?)", id, C.GoString(content), C.GoString(tags))

	if err != nil {
		tx.Rollback()
		return C.CString(err.Error())
	}
	tx.Commit()
	return C.CString("Success")
}

//export DeleteRecord
func DeleteRecord(dbPath *C.char, id int64) *C.char {
	db, _ := sql.Open("sqlite", C.GoString(dbPath))
	defer db.Close()
	tx, _ := db.Begin()

	// We must fetch content/tags BEFORE deleting from master to clean up FTS
	var c, t string
	db.QueryRow("SELECT content, tags FROM indexed_contents WHERE id = ?", id).Scan(&c, &t)

	// 1. Delete from Master
	tx.Exec("DELETE FROM indexed_contents WHERE id = ?", id)

	// 2. Delete from FTS5 (Crucial for external content tables)
	tx.Exec("INSERT INTO indexed_contents_fts(indexed_contents_fts, rowid, content, tags) VALUES('delete', ?, ?, ?)", id, c, t)

	tx.Commit()
	return C.CString("Success")
}

func main() {}
