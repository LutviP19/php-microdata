<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Structs\Dashboard\v1;


use App\Core\Database\SchemaProperty;

class SallaryStruct {
    #[SchemaProperty(description: 'ID Primary Key', numeric: true, required: true)]
    public int $emp_no;

    #[SchemaProperty(description: 'Amount of Sallary', numeric: true, gte: 0)]
    public int $salary;

    #[SchemaProperty(description: 'From date', custom: 'string')]
    public string $from_date;

    #[SchemaProperty(description: 'To date', custom: 'string')]
    public string $to_date;

}