<?php 
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 */


namespace App\Structs;

use App\Core\Database\SchemaProperty;

class DashboardStruct {

   #[SchemaProperty(description: 'User display name', required: true, min: 3)]
    public string $username;

    #[SchemaProperty(description: 'Primary contact', required: true, email: true)]
    public string $email;

    #[SchemaProperty(description: 'User age', numeric: true, gte: 18, lte: 99)]
    public int $age;

    #[SchemaProperty(description: 'Website URL', custom: 'url')]
    public string $website;
}