<?php
/**
 * Engine-group cutoffs for Apollo-style Saturn stages.
 * Reference mission times are advisory in the generic training mode.
 * Only a clearly labelled center engine can be shut down; never guess which
 * of five engine records is the center engine from its row position.
 */
function moonSimCenterEngineCutoff(mysqli $conn, string $stage): bool
{
    if (!in_array($stage, ['First Stage', 'Second Stage'], true)) {
        return false;
    }
    $stageSql = $conn->real_escape_string($stage);
    $result = $conn->query(
        "SELECT DISTINCT engine_name FROM EngineStatus
         WHERE engine_stage='$stageSql'
           AND component='Combustion Chamber'"
    );
    if (!$result) return false;
    $names = [];
    while ($row=$result->fetch_assoc()) $names[]=(string)$row['engine_name'];
    // Without five distinctly modelled engines, an aggregate thrust record
    // cannot support a physically meaningful center-engine cutoff.
    if (count($names)!==5) return false;
    $center = array_values(array_filter($names, static function(string $name): bool {
        return (bool)preg_match('/\b(center|centre|central)\b/i', $name);
    }));
    if (count($center)!==1) return false;
    $engineSql=$conn->real_escape_string($center[0]);
    return (bool)$conn->query(
        "UPDATE EngineStatus SET status='OFF'
         WHERE engine_stage='$stageSql'
           AND engine_name='$engineSql'
           AND component='Combustion Chamber'
           AND status='ON'"
    );
}
