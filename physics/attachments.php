<?php

declare(strict_types=1);

function physicsRootId(int $objectId, array $objects): int
{
    $seen = [];
    $current = $objectId;

    while (
        isset($objects[$current])
        && $objects[$current]['is_attached']
        && $objects[$current]['parent_object_id'] !== null
    ) {
        if (isset($seen[$current])) {
            throw new RuntimeException('Attachment cycle detected.');
        }

        $seen[$current] = true;
        $current = (int)$objects[$current]['parent_object_id'];
    }

    return $current;
}

function physicsRootMembers(array $objects): array
{
    $roots = [];

    foreach ($objects as $id => $_object) {
        $root = physicsRootId((int)$id, $objects);
        $roots[$root][] = (int)$id;
    }

    return $roots;
}

function physicsSynchronizeAttachments(array &$objects, array $rootMembers): void
{
    foreach ($rootMembers as $rootId => $memberIds) {
        $root = $objects[$rootId];

        foreach ($memberIds as $memberId) {
            if ($memberId === $rootId) {
                continue;
            }

            /*
             * Phase-one point-object behavior:
             * attached objects share the root state exactly.
             *
             * The relative attachment offset fields are already stored
             * in the DB for later rigid-body rendering/collision work.
             */
            foreach ([
                'x_m', 'y_m', 'z_m',
                'vx_m_s', 'vy_m_s', 'vz_m_s',
                'pitch_deg', 'yaw_deg', 'roll_deg',
                'pitch_rate_deg_s', 'yaw_rate_deg_s', 'roll_rate_deg_s',
            ] as $field) {
                $objects[$memberId][$field] = $root[$field];
            }
        }
    }
}
