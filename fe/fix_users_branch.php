<?php
$contents = file_get_contents("solvingh_engineer_maintenance (1).sql");

// Parse users block
$pattern_users = "/(--\s*?Dumping data untuk tabel `users`[\s\S]*?(?=\n--\s*?Dumping))/u";

if (preg_match($pattern_users, $contents, $matches)) {
    $users_block = $matches[1];
    
    // We see that branch_id's often use 1. But branches in the new DB only have IDs starting from 2, specifically 29 -> 00HOAL
    // Wait, HO01 was branch_id 1 in the old DB. In the new DB:
    // 29, '00HOAL', 'HOLDING ALAM SUTERA'
    // Let's check if there is an exact mapping of branch IDs we can use to update ALL foreign keys (users, electricity_meters, daily_records, tickets, etc.)
}

$branches = [
    // Old ID => New ID (if changed)
    1 => 29, // Mapping HO01 to Holding Alam Sutera (00HOAL) for fallback
];

foreach ($branches as $old => $new) {
    if ($old !== $new) {
        $contents = preg_replace_callback("/(REPLACE INTO `users`[^;]+;)/su", function($m) use ($old, $new) {
            $block = $m[1];
            // Regex to target the branch_id in the tuple for `users`
            // (`id`, `name`, `email`, `email_verified_at`, `password`, `branch_id`, ...)
            // (1, 'Super Admin', 'superadmin@gmail.com', NULL, 'XXX', 1, ...)
            
            // Just matching the integers: (\d+,\s*'.*?',\s*'.*?',\s*(?:NULL|'.*?'),\s*'.*?',\s*)(\d+)
            $block = preg_replace_callback("/(\(\d+,\s*'[^']+',\s*'[^']+',\s*(?:NULL|'[^']+'),\s*'[^']+',\s*)(" . $old . ")(\s*,)/", function($mm) use ($new) {
                return $mm[1] . $new . $mm[3];
            }, $block);
            
            return $block;
        }, $contents);
    }
}

file_put_contents("solvingh_engineer_maintenance (1).sql", $contents);
echo "Users branch_id 1 updated to 29.\n";
