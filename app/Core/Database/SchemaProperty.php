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
        public string $description = '',        // Description for humans
        public bool $omitempty = false,         // Go: 'omitempty'
        public bool $required = false,          // Go: 'required'        
        public bool $email = false,             // Go: 'email'
        public bool $boolean = false,           // Go: 'boolean'
        public bool $numeric = false,           // Go: 'numeric'
        public int|float|null $gt = null,       // Go: 'gt=X'
        public int|float|null $gte = null,      // Go: 'gte=X'
        public int|float|null $lt = null,       // Go: 'lt=X'
        public int|float|null $lte = null,      // Go: 'lte=X'
        public int|float|null $min = null,      // Go: 'min=X'
        public int|float|null $max = null,      // Go: 'max=X'
        public string $custom = '',             // Any other Go tags (e.g., 'url,alpha')
    ) {}
}
