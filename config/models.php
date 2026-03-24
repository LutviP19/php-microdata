<?php
// file: config/models.php

return [
    'modules' => [
        // 'Folder_Tujuan' => 'Pattern_Regex_Model'
        'Auth' => '/(Auth|Roles|Permission|Users|Register)/i',
        'Dashboard' => '/(Stats|Report|Dashboard|Employee|Statistic)/i',
        'Inventory' => '/(Stock|Product|Supplier|Warehouse)/i',
        'User'      => '/(Profile|Account|Role|Permission)/i',
    ],
    
    // Pemetaan khusus jika nama Data berbeda dengan nama Struct (Optional)
    'data_mapping' => [
        'Statistc' => 'StatsData',
        'Report'   => 'ReportData'
    ]
];