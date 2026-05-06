<?php

/**
 * ProtoBridge FFI Class to Serialize protobuff with librust_protobuf.
 * @package Microdata-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */

namespace App\Core\FFI;

use FFI;
use RuntimeException;

class ProtoBridge
{
    private readonly FFI $ffi;
    private readonly string $libPath;

    public function __construct(?string $libPath = null)
    {
        $defaultPath = defined('BASEPATH_FFI')
            ? BASEPATH_FFI . '/lib/librust_protobuf.so'
            : __DIR__ . '/../../../ffi/lib/librust_protobuf.so';

        $this->libPath = $libPath ?? $defaultPath;

        if (!file_exists($this->libPath)) {
            throw new RuntimeException("Rust Library (.so) tidak ditemukan di: " . $this->libPath);
        }

        $this->ffi = FFI::cdef("
            typedef struct {
                unsigned char* data;
                size_t len;
            } ProtoBuffer;

            ProtoBuffer encode_generic(
                const char* content, 
                const char* json_metadata, 
                const unsigned char* payload_ptr, 
                size_t payload_len
            );
            char* decode_generic(const unsigned char* binary_ptr, size_t len);
            void free_proto_buffer(ProtoBuffer buf);
            void free_string(char* s);
        ", $this->libPath);
    }

    public function pack(mixed $content, array $metadata = [], string $binaryPayload = '', bool $outputBinary = true): string
    {
        if (!$content) {
            return null;
        }

        // Convert content to JSON
        $contentData = is_string($content) ? [str_replace(" ", "", $content)] : $content;
        $content = json_encode($contentData, JSON_FORCE_OBJECT);

        // Pastikan semua value adalah string agar cocok dengan map<string, string> di Rust
        $sanitizedMetadata = array_map(fn ($value) => (string) $value, $metadata);
        $jsonMetadata = json_encode($sanitizedMetadata, JSON_FORCE_OBJECT);

        $payloadLen = strlen($binaryPayload);
        $cPayload = null;

        if ($payloadLen > 0) {
            $cPayload = $this->ffi->new("unsigned char[$payloadLen]", false);
            FFI::memcpy($cPayload, $binaryPayload, $payloadLen);
        }

        $buf = $this->ffi->encode_generic(
            $content,
            $jsonMetadata,
            $cPayload ? $this->ffi->cast("unsigned char*", $cPayload) : null,
            $payloadLen
        );

        $binary = FFI::string($buf->data, $buf->len);
        $this->ffi->free_proto_buffer($buf);

        if ($outputBinary) {
            return $binary;
        }

        return base64_encode($binary);
    }

    public function unpack(string $binaryData, bool $sourceiSBinary = true): array
    {

        if (!$binaryData) {
            return null;
        }

        if (!$sourceiSBinary) {
            $binaryData = base64_decode($binaryData);
            // dd($binaryData);
        }

        $len = strlen($binaryData);
        if ($len === 0) {
            return [];
        }

        // Buat buffer di sisi C untuk menampung binaryData
        $cBuf = $this->ffi->new("unsigned char[$len]", false);
        FFI::memcpy($cBuf, $binaryData, $len);

        // Panggil Rust untuk decode
        // Kita cast ke (const unsigned char*) agar sesuai dengan signature C
        $jsonPtr = $this->ffi->decode_generic($this->ffi->cast("unsigned char*", $cBuf), $len);

        if (FFI::isNull($jsonPtr)) {
            return [];
        }

        $jsonStr = FFI::string($jsonPtr);

        // WAJIB: Bebaskan string JSON yang dibuat oleh Rust (CString::into_raw)
        $this->ffi->free_string($jsonPtr);

        return json_decode($jsonStr, true) ?? [];
    }
}

// // Contoh Penggunaan:
// $bridge = new ProtoBridge();

// // Simulasikan Encode
// $bin = $bridge->pack("/v1/sso/auth");
// echo "Binary Size: " . strlen($bin) . " bytes\n";

// // Simulasikan Decode
// $data = $bridge->unpack($bin);
// print_r($data);
