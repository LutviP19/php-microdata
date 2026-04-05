<?php
// file: config/models.php

return [
    // Path (Namespace) for Data and Structs
    'modules' => [
        // 'Folder_Tujuan' => 'Pattern_Regex_Model'
        'Auth' => '/(Auth|Roles|Permission|Users|Register)/i',
        // 'Dashboard' => '/(Stats|Report|Dashboard|Employee|Statistic)/i',
        'Inventory' => '/(Stock|Product|Supplier|Warehouse)/i',
        'User'      => '/(Profile|Account|Role|Permission)/i',

        // Version 1
        "v1" => [
            'Dashboard' => '/(Dashboard|Stats-v1|Report-v1|Employee|Statistic)/i',
        ],
        
    ],
    
    // Pemetaan khusus jika nama Data berbeda dengan nama Struct (Optional)
    'data_mapping' => [
        'Statistc' => 'StatsData',
        'Report'   => 'ReportData'
    ]
];