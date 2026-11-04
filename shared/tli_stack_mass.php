<?php
declare(strict_types=1);
/** Match physics derived_state for an attached Saturn S-IVB/CSM/LM stack. */
function moonSimTliStackMass(array $cm,array $lm,array $c):float {
 $rcsCm=max(0.0,(float)($cm['RCS']??0));
 $rcsLm=max(0.0,(float)($lm['RCS']??0));
 if($rcsCm>2*$c['csm_rcs_initial_propellant'])$rcsCm=$rcsCm/3329*$c['csm_rcs_initial_propellant'];
 if($rcsLm>2*$c['lm_rcs_initial_propellant'])$rcsLm=$rcsLm/3329*$c['lm_rcs_initial_propellant'];
 if((int)($cm['stage3']??0)!==1||(int)($cm['stage1']??0)!==0||(int)($cm['stage2']??0)!==0)
  throw new RuntimeException('TLI requires S-IVB without lower stages');
 $mass=$c['command_module_empty_mass']+$rcsCm+$c['stage3_empty_mass']+max(0.0,(float)($cm['s3fuel']??0));
 if((int)($cm['service_module']??0)===1)$mass+=$c['service_module_empty_mass']+max(0.0,(float)($cm['fuel']??0));
 if((int)($cm['escape']??0)===1)$mass+=$c['escape_tower_empty_mass']+max(0.0,(float)($cm['escape_fuel']??$cm['excape_fuel']??0));
 $mass+=$c['lander_ascent_empty_mass']+max(0.0,(float)($lm['fuel_ascent']??0))+$rcsLm;
 if((int)($lm['descent']??0)===1)$mass+=$c['lander_descent_empty_mass']+max(0.0,(float)($lm['fuel']??0));
 return $mass;
}
