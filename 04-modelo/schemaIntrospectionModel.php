<?php

if (!function_exists('tabla_existe')) {
    function tabla_existe(mysqli $db, string $table): bool
    {
        $sql = "
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
            LIMIT 1
        ";
        $st = mysqli_prepare($db, $sql);
        if (!$st) {
            return false;
        }

        mysqli_stmt_bind_param($st, 's', $table);
        if (!mysqli_stmt_execute($st)) {
            mysqli_stmt_close($st);
            return false;
        }

        $rs = mysqli_stmt_get_result($st);
        $ok = ($rs && mysqli_fetch_row($rs));
        mysqli_stmt_close($st);

        return (bool)$ok;
    }
}

if (!function_exists('columna_existe')) {
    function columna_existe(mysqli $db, string $table, string $column): bool
    {
        $sql = "
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = ?
              AND column_name = ?
            LIMIT 1
        ";
        $st = mysqli_prepare($db, $sql);
        if (!$st) {
            return false;
        }

        mysqli_stmt_bind_param($st, 'ss', $table, $column);
        if (!mysqli_stmt_execute($st)) {
            mysqli_stmt_close($st);
            return false;
        }

        $rs = mysqli_stmt_get_result($st);
        $ok = ($rs && mysqli_fetch_row($rs));
        mysqli_stmt_close($st);

        return (bool)$ok;
    }
}
