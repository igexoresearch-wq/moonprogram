<?php
declare(strict_types=1);

/**
 * Pure launch stage transition rules. The database executor is the only
 * component allowed to mutate stage flags and Saturn engine status.
 *
 * Staging=1 is an explicit separation request. Exhausted propellant is an
 * automatic separation request for S-IC and S-II only. S-IVB separation must
 * always be explicitly commanded after parking-orbit insertion.
 */
function moonSimSaturnNextAction(
    array $state, array $runtime, int $seconds, int $launchEvents,
    bool $automaticEnabled
): ?array {
    if ((int)($state['ABORT'] ?? 0) === 1) return null;
    if ((int)($runtime['staging_hold'] ?? 0) === 1) {
        return ['type'=>'clear_staging'];
    }
    if ($seconds < 0) return null;
    $first=(int)($state['stage1'] ?? 0) === 1;
    $second=(int)($state['stage2'] ?? 0) === 1;
    $third=(int)($state['stage3'] ?? 0) === 1;
    $requested=(int)($state['staging'] ?? 0) === 1;
    $stage=null;
    if ($first && $second && $third) {
        $stage=['name'=>'First Stage','flag'=>'stage1','fuel'=>'s1fuel','latch'=>'sic_ignited','program'=>19];
    } elseif (!$first && $second && $third) {
        $stage=['name'=>'Second Stage','flag'=>'stage2','fuel'=>'s2fuel','latch'=>'sii_ignited','program'=>20];
    } elseif (!$first && !$second && $third) {
        $stage=['name'=>'Third Stage','flag'=>'stage3','fuel'=>'s3fuel','latch'=>'sivb_ignited','program'=>null];
    }
    if ($stage===null) return null;
    $fuel=(float)($state[$stage['fuel']] ?? 0.0);
    if ($fuel < 0 || !is_finite($fuel)) return null;
    if ($requested && ($stage['name']!=='Third Stage' || $launchEvents>=2)) {
        return ['type'=>'separate']+$stage;
    }
    if ($launchEvents===1 && $stage['name']!=='Third Stage' && $fuel<=0.0) {
        return ['type'=>'separate']+$stage;
    }
    if ($automaticEnabled && $launchEvents===1 && !$requested && $fuel>0.0
        && (int)($runtime[$stage['latch']] ?? 0)===0) {
        return ['type'=>'ignite']+$stage;
    }
    return null;
}
