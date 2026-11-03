<?php
declare(strict_types=1);
require_once __DIR__.'/../shared/saturn_stage_rules.php';

function expectAction(?array $action, ?string $type, ?string $stage, string $reason): void {
    if ($type===null) {
        if ($action!==null) throw new RuntimeException($reason.': unexpected '.json_encode($action));
    } elseif (($action['type']??null)!==$type || ($action['name']??null)!==$stage) {
        throw new RuntimeException($reason.': got '.json_encode($action));
    }
    echo 'PASS '.$reason.PHP_EOL;
}
$base=['ABORT'=>0,'stage1'=>1,'stage2'=>1,'stage3'=>1,
       's1fuel'=>100,'s2fuel'=>100,'s3fuel'=>100,'staging'=>0];
$latch=['sic_ignited'=>0,'sii_ignited'=>0,'sivb_ignited'=>0,'staging_hold'=>0];
expectAction(moonSimSaturnNextAction($base,$latch,-1,1,true),null,null,'No ignition before liftoff');
expectAction(moonSimSaturnNextAction($base,$latch,0,1,true),'ignite','First Stage','S-IC ignition');
expectAction(moonSimSaturnNextAction($base,$latch,0,1,false),null,null,'Manual/AGC-off leaves engines alone');
expectAction(moonSimSaturnNextAction($base,$latch+['unused'=>1],0,0,true),null,null,'No automatic ignition outside active launch');
$started=$latch;$started['sic_ignited']=1;
expectAction(moonSimSaturnNextAction($base,$started,5,1,true),null,null,'One shot S-IC ignition');
$empty=$base;$empty['s1fuel']=0;
expectAction(moonSimSaturnNextAction($empty,$started,150,1,true),'separate','First Stage','S-IC burnout separation');
$manual=$base;$manual['staging']=1;
expectAction(moonSimSaturnNextAction($manual,$started,30,1,true),'separate','First Stage','Manual stage request honored');
$hold=$latch;$hold['staging_hold']=1;
expectAction(moonSimSaturnNextAction($manual,$hold,30,1,true),'clear_staging',null,'Stage event held then cleared');
$abort=$empty;$abort['ABORT']=1;
expectAction(moonSimSaturnNextAction($abort,$latch,150,1,true),null,null,'Abort blocks staging');
$two=$base;$two['stage1']=0;
expectAction(moonSimSaturnNextAction($two,$latch,170,1,true),'ignite','Second Stage','S-II ignition');
$two['s2fuel']=0;
expectAction(moonSimSaturnNextAction($two,$latch,560,1,true),'separate','Second Stage','S-II burnout separation');
$third=$two;$third['stage2']=0;$third['s3fuel']=120;
expectAction(moonSimSaturnNextAction($third,$latch,560,1,true),'ignite','Third Stage','S-IVB first ignition');
$thirdLatch=$latch;$thirdLatch['sivb_ignited']=1;
expectAction(moonSimSaturnNextAction($third,$thirdLatch,700,2,true),null,null,'No S-IVB re-ignition after parking orbit');
$third['s3fuel']=0;
expectAction(moonSimSaturnNextAction($third,$thirdLatch,701,1,true),null,null,'No automatic S-IVB jettison on fuel depletion');
$third['staging']=1;
expectAction(moonSimSaturnNextAction($third,$thirdLatch,730,2,true),'separate','Third Stage','Explicit S-IVB jettison after parking orbit');
expectAction(moonSimSaturnNextAction($third,$thirdLatch,730,1,true),null,null,'No early S-IVB separation');
$manualFirst=$base;$manualFirst['thrust']=65;
expectAction(moonSimSaturnNextAction($manualFirst,$latch,0,1,false),
    'ignite','First Stage','Manual throttle can ignite stage with AGC off');
$restart=$third;$restart['staging']=0;$restart['s3fuel']=80;$restart['thrust']=40;
$restartLatch=$thirdLatch;$restartLatch['sivb_restart_armed']=1;
expectAction(moonSimSaturnNextAction($restart,$restartLatch,800,2,false),
    'restart','Third Stage','Explicit throttle re-ignites S-IVB for TLI');
$restartLatch['sivb_restart_armed']=0;
expectAction(moonSimSaturnNextAction($restart,$restartLatch,801,2,false),
    null,null,'S-IVB does not repeatedly reignite each tick');
$restart['thrust']=0;
expectAction(moonSimSaturnNextAction($restart,$restartLatch,802,2,false),
    'arm_restart','Third Stage','S-IVB cutoff arms a later explicit restart');
$restart['s3fuel']=0;$restart['thrust']=60;
$restartLatch['sivb_restart_armed']=1;
expectAction(moonSimSaturnNextAction($restart,$restartLatch,804,2,false),
    null,null,'Empty S-IVB cannot restart');
echo 'Saturn stage decision rules passed (database/physics integration still requires live trial).'.PHP_EOL;
