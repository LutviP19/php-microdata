<?php 
/**
 * @author LutviP19 <lutvip19@gmail.com>
 * DefaultStruct for casting default data types
 */


namespace App\Structs;


use App\Core\Database\SchemaProperty;


class DefaultStruct 
{
    // Pagination

    #[SchemaProperty(description: 'Pagination Page', omitempty: true, numeric: true, gte: 1)]
    public int $page;

    #[SchemaProperty(description: 'Pagination Limit', omitempty: true, numeric: true, gte: 1)]
    public int $limit;

    #[SchemaProperty(description: 'Pagination Offset', omitempty: true, numeric: true, gte: 0)]
    public int $offset;

    #[SchemaProperty(description: 'Pagination Total', omitempty: true, numeric: true, gte: 0)]
    public int $total;

    // General

    #[SchemaProperty(description: 'ID Primary Key', omitempty: true, numeric: true)]
    public int $id;

    #[SchemaProperty(description: 'Product or Employee Name', omitempty: true, min: 3)]
    public string $name;

    #[SchemaProperty(description: 'Category Label', omitempty: true)]
    public string $category;

    #[SchemaProperty(description: 'Financial Value', omitempty: true, numeric: true, custom: 'required_with_all=Field1 Field2', gte: 0)]
    public float $price;

    #[SchemaProperty(description: 'Stock Quantity', omitempty: true, numeric: true, gte: 0)]
    public int $stock;

    #[SchemaProperty(description: 'Availability Status', omitempty: true, boolean: true)]
    public bool $is_active;

    #[SchemaProperty(description: 'Internal Tags', omitempty: true, custom: 'oneof=red green')]
    public string $tags;

}