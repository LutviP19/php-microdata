package main

import "C"
import (
	"unsafe"
	"time"
	"math/rand"
	"errors"
	"sync"
)

//export ResizeImages
func ResizeImages(input **C.char, count C.int, failedOut ***C.char, failedCount *C.int) {
	// because this is a C binding and C doesn't have any nice structures built-in,
	// we have to pass the data as a char[] pointer and provide the count of items as
	// a second parameter

	// to avoid having to create a custom struct, we return the data by having them passed as references
	// the triple asterisk means it's a pointer to char array, the single asterisk means it's a pointer to
	// an integer

	paths := unsafe.Slice(input, int(count)) // we have to make a slice out of the input
	goPaths := make([]string, count)         // create a new Go slice with the correct length
	for i, path := range paths {
		goPaths[i] = C.GoString(path) // convert the C-strings to Go-strings
	}

	failed := ResizeImagesGo(goPaths) // call the Go function and assign the result

	// the parts below are some C-level shenanigans, basically you need to allocate (C.malloc) enough memory
	// to hold the amount of pointers that will be assigned, which is the length of the failed slice
	failedAmount := len(failed)
	ptrSize := unsafe.Sizeof(uintptr(0))
	// C.malloc is a direct allocation of memory
	cArray := C.malloc(C.size_t(failedAmount) * C.size_t(ptrSize))
	cStrs := unsafe.Slice((**C.char)(cArray), failedAmount)

	for i, str := range failed { // iterate over the failed paths
		// C.CString uses malloc in the background
		cStrs[i] = C.CString(str)
	}

	*failedOut = (**C.char)(cArray)    // assign the array to the reference input parameter
	*failedCount = C.int(failedAmount) // assign the count of failed items to the reference input parameter
}

func ResizeImage(path string) error {
	time.Sleep(300 * time.Millisecond)

	if rand.Int()%2 == 0 {
		return errors.New("test")
	}

	return nil
}

func ResizeImagesGo(paths []string) []string {
	var waitGroup sync.WaitGroup // create a wait group - once it's empty, everything has been processed
	var mutex sync.Mutex         // a mutex to safely write into the failed slice below
	failed := make([]string, 0)  // create a slice that can contain strings and has initial length of zero

	for _, path := range paths { // iterate over all paths
		path := path     // this recreates the path variable inside the current scope to avoid race conditions
		waitGroup.Add(1) // add one to the wait group
		go func() {      // run this in a goroutine (similar to threads in other languages)
			defer waitGroup.Done() // after this function finishes, waitGroup.Done() will be called
			err := ResizeImage(path)
			if err != nil { // if we have an error
				mutex.Lock()                  // lock the mutex to make sure only one goroutine is writing to the failed slice
				failed = append(failed, path) // add a new path to the list of failed paths
				mutex.Unlock()                // unlock the mutex so that any other goroutine can lock it again
			}
		}()
	}

	waitGroup.Wait() // wait until all wait groups are done

	return failed
}


func main() {}
