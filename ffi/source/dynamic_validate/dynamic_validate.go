package main

import (
	"C"
	"encoding/json"
	"strings"

	ut "github.com/go-playground/universal-translator"
	"github.com/go-playground/validator/v10"

	// These are the missing translation packages
	en_translations "github.com/go-playground/validator/v10/translations/en"
	id_translations "github.com/go-playground/validator/v10/translations/id"
)
import (
	"github.com/go-playground/locales/en"
	"github.com/go-playground/locales/id"
)

type ValidationRequest struct {
	Data  map[string]interface{} `json:"data" validate:"required"`
	Rules map[string]string      `json:"rules" validate:"required"`
	Lang  string                 `json:"lang"` // "en" or "id"
}

var (
	validate *validator.Validate
	uni      *ut.UniversalTranslator
)

func init() {
	validate = validator.New()

	// Setup Locales
	enLocale := en.New()
	idLocale := id.New()

	// Setup Universal Translator
	uni = ut.New(enLocale, enLocale, idLocale)

	// Register English
	transEn, _ := uni.GetTranslator("en")
	en_translations.RegisterDefaultTranslations(validate, transEn)

	// Register Indonesian
	transId, _ := uni.GetTranslator("id")
	id_translations.RegisterDefaultTranslations(validate, transId)
}

//export ValidateDynamic
func ValidateDynamic(input *C.char) *C.char {
	var req ValidationRequest
	if err := json.Unmarshal([]byte(C.GoString(input)), &req); err != nil {
		return C.CString(`{"error": "Invalid request format"}`)
	}

	// Default to English if lang is not provided or not supported
	lang := req.Lang
	if lang == "" {
		lang = "en"
	}

	trans, _ := uni.GetTranslator(lang)
	errors := make(map[string]string)

	for field, rule := range req.Rules {
		value := req.Data[field]
		err := validate.Var(value, rule)

		if err != nil {
			if errs, ok := err.(validator.ValidationErrors); ok {
				// Translate the error using the selected language
				translatedMsg := errs[0].Translate(trans)

				// Replace the placeholder
				replacedMsg := strings.Replace(translatedMsg, "''", "'"+field+"'", 1)

				// Collapse all extra internal spaces and trim edges
				// strings.Fields splits by any whitespace; Join puts them back with exactly one space.
				cleanMsg := strings.Join(strings.Fields(replacedMsg), " ")

				if len(cleanMsg) > 0 {
					// Uppercase only the first character
					errors[field] = strings.ToUpper(cleanMsg[:1]) + cleanMsg[1:]
				}
			}
		}
	}

	if len(errors) > 0 {
		resp, _ := json.Marshal(map[string]interface{}{"errors": errors})
		return C.CString(string(resp))
	}

	return C.CString(`{"status": "success"}`)
}

func main() {}
