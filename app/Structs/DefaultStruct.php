<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Structs;


use App\Core\Database\SchemaProperty;


class DefaultStruct 
{
    #[SchemaProperty(description: 'ID Primary Key', numeric: true, required: true)]
    public int $id;

    #[SchemaProperty(description: 'Product or Employee Name', required: true, min: 3)]
    public string $name;

    #[SchemaProperty(description: 'Category Label', required: true)]
    public string $category;

    #[SchemaProperty(description: 'Financial Value', numeric: true, custom: 'float', gte: 0)]
    public float $price;

    #[SchemaProperty(description: 'Stock Quantity', numeric: true, gte: 0)]
    public int $stock;

    #[SchemaProperty(description: 'Availability Status', boolean: true)]
    public bool $is_active;

    #[SchemaProperty(description: 'Internal Tags', custom: 'string')]
    public string $tags;

}