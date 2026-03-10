package main

import "C"

//export Fibonacci
func Fibonacci(n C.int) C.int {
	return C.int(fibonacciGo(int(n)))
}

func fibonacciGo(n int) int {
	if n <= 1 {
		return n
	}
	return fibonacciGo(n-1) + fibonacciGo(n-2)
}


func main() {}
