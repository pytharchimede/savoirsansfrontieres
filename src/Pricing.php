<?php
declare(strict_types=1);

/** Pricing is stored in euro cents to avoid floating-point rounding. */
function ssf_pricing(): array {
    $stmt=ssf_db()->query("SELECT code,amount_cents FROM site_pricing WHERE code IN ('hourly','monthly')");
    $prices=['hourly'=>1250,'monthly'=>10000];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $prices[(string)$row['code']]=(int)$row['amount_cents'];
    }
    return $prices;
}
function ssf_euros(int $cents): string {
    return number_format($cents/100,2,',',' ').' €';
}
