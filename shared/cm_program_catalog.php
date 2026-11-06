<?php
declare(strict_types=1);
/**
 * Single source of truth for currently wired CM DSKY programs.
 * A number absent here must be implemented and tested before selection.
 */
function moonSimCmSelectablePrograms():array {
 return [0,9,10,25,28,29,30,31,32,33,34,35,36,37,38,39,40];
}
function moonSimCmProgramPath(int $program):?string {
 if($program===0||!in_array($program,moonSimCmSelectablePrograms(),true))return null;
 $path=__DIR__.'/../physics/prog/'.$program.'.php';
 return is_file($path)?$path:null;
}
