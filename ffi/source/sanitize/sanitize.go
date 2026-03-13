package main

import (
	"C"
	"encoding/json"
	"github.com/microcosm-cc/bluemonday"
)

var p = bluemonday.UGCPolicy()

// sanitizeValue recursively traverses maps and slices
func sanitizeValue(v interface{}) interface{} {
	switch val := v.(type) {
	case string:
		return p.Sanitize(val) // Only strings are sanitized
	case map[string]interface{}:
		for k, item := range val {
			val[k] = sanitizeValue(item)
		}
	case []interface{}:
		for i, item := range val {
			val[i] = sanitizeValue(item)
		}
	}
	return v // Numbers, booleans, and null are returned as-is
}

//export SanitizeJSON
func SanitizeJSON(input *C.char) *C.char {
	var data interface{}
	if err := json.Unmarshal([]byte(C.GoString(input)), &data); err != nil {
		return C.CString(C.GoString(input)) // Return original if invalid
	}

	sanitized := sanitizeValue(data)
	res, _ := json.Marshal(sanitized)
	return C.CString(string(res))
}

func main() {}
