<?php

declare(strict_types=1);

/**
 * Restore the seeded launch state.
 *
 * Earth/Moon/craft positions, velocities, orientations, attachments,
 * and all propellant quantities are restored atomically.
 */
function moonResetSimulation(PDO $pdo): array
{
    $pdo->beginTransaction();

    try {
        $restoreObjects = $pdo->prepare(
            "UPDATE space_objects AS o
             JOIN initial_object_states AS i
               ON i.object_id = o.id
             SET
                 o.x_cm = i.x_cm,
                 o.y_cm = i.y_cm,
                 o.z_cm = i.z_cm,
                 o.x_subcm = i.x_subcm,
                 o.y_subcm = i.y_subcm,
                 o.z_subcm = i.z_subcm,
                 o.vx_cm_s = i.vx_cm_s,
                 o.vy_cm_s = i.vy_cm_s,
                 o.vz_cm_s = i.vz_cm_s,
                 o.pitch_deg = i.pitch_deg,
                 o.yaw_deg = i.yaw_deg,
                 o.roll_deg = i.roll_deg,
                 o.pitch_rate_deg_s = i.pitch_rate_deg_s,
                 o.yaw_rate_deg_s = i.yaw_rate_deg_s,
                 o.roll_rate_deg_s = i.roll_rate_deg_s,
                 o.surface_locked = i.surface_locked,
                 o.on_surface = i.on_surface"
        );
        $restoreObjects->execute();

        $pdo->exec(
            "UPDATE propellant_systems
             SET remaining_kg = capacity_kg"
        );

        $pdo->exec("DELETE FROM object_attachments");

        $pdo->exec(
            "INSERT INTO object_attachments (
                 parent_object_id,
                 child_object_id,
                 attachment_type
             )
             SELECT
                 parent_object_id,
                 child_object_id,
                 attachment_type
             FROM initial_object_attachments"
        );

        $state = $pdo->prepare(
            "UPDATE simulation_state
             SET
                 is_running = 0,
                 tick_seconds = 1.000000,
                 time_scale = 1.000000,
                 simulated_time_seconds = 0.000000,
                 last_tick_at = NOW(6)
             WHERE id = 1"
        );
        $state->execute();

        $pdo->commit();

        return [
            'success' => true,
            'message' => 'Simulation restored to launch state.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}
