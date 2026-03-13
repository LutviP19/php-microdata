<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Core\Database;

// Add this import!
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SchemaProperty {
    public function __construct(
        public string $description = '', // Description for humans
        public bool $required = false,   // Go: 'required'
        public bool $email = false,      // Go: 'email'
        public bool $numeric = false,    // Go: 'numeric'
        public ?int $gte = null,         // Go: 'gte=X'
        public ?int $lte = null,         // Go: 'lte=X'
        public ?int $min = null,         // Go: 'min=X'
        public ?int $max = null,         // Go: 'max=X'
        public string $custom = '',      // Any other Go tags (e.g., 'url,alpha')
    ) {}
}
