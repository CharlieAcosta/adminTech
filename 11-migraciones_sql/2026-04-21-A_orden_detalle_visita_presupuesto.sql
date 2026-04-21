-- Preserva el orden visual entre visita y presupuesto y corrige jornales.
DROP PROCEDURE IF EXISTS admintech_add_column_if_missing;
DROP PROCEDURE IF EXISTS admintech_add_index_if_missing;

DELIMITER //

CREATE PROCEDURE admintech_add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND column_name = p_column_name
    ) THEN
        SET @sql_admintech_add_column = CONCAT(
            'ALTER TABLE `',
            p_table_name,
            '` ADD COLUMN ',
            p_column_definition
        );
        PREPARE stmt_admintech_add_column FROM @sql_admintech_add_column;
        EXECUTE stmt_admintech_add_column;
        DEALLOCATE PREPARE stmt_admintech_add_column;
    END IF;
END//

CREATE PROCEDURE admintech_add_index_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND index_name = p_index_name
    ) THEN
        SET @sql_admintech_add_index = CONCAT(
            'ALTER TABLE `',
            p_table_name,
            '` ADD INDEX `',
            p_index_name,
            '` ',
            p_index_columns
        );
        PREPARE stmt_admintech_add_index FROM @sql_admintech_add_index;
        EXECUTE stmt_admintech_add_index;
        DEALLOCATE PREPARE stmt_admintech_add_index;
    END IF;
END//

DELIMITER ;

CALL admintech_add_column_if_missing(
    'visita_tarea_material',
    'orden',
    '`orden` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `cantidad`'
);

CALL admintech_add_column_if_missing(
    'visita_tarea_mano_obra',
    'orden',
    '`orden` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `observaciones`'
);

CALL admintech_add_column_if_missing(
    'presupuesto_tarea_material',
    'orden',
    '`orden` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `id_material`'
);

CALL admintech_add_column_if_missing(
    'presupuesto_tarea_mano_obra',
    'orden',
    '`orden` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `id_jornal`'
);

CREATE TEMPORARY TABLE tmp_admintech_vtm_orden (
    id INT NOT NULL PRIMARY KEY,
    orden INT UNSIGNED NOT NULL
) ENGINE=Memory;

INSERT INTO tmp_admintech_vtm_orden (id, orden)
SELECT ordenado.id, ordenado.orden
FROM (
    SELECT
        base.id,
        @admintech_vtm_orden := IF(@admintech_vtm_tarea = base.id_tarea, @admintech_vtm_orden + 1, 1) AS orden,
        @admintech_vtm_tarea := base.id_tarea
    FROM (
        SELECT id, id_tarea
        FROM visita_tarea_material
        ORDER BY id_tarea ASC, id ASC
    ) AS base
    CROSS JOIN (SELECT @admintech_vtm_orden := 0, @admintech_vtm_tarea := 0) AS vars
) AS ordenado;

UPDATE visita_tarea_material AS vtm
JOIN tmp_admintech_vtm_orden AS tmp ON tmp.id = vtm.id
SET vtm.orden = tmp.orden
WHERE vtm.orden = 0;

DROP TEMPORARY TABLE tmp_admintech_vtm_orden;

CREATE TEMPORARY TABLE tmp_admintech_vtmo_orden (
    id INT NOT NULL PRIMARY KEY,
    orden INT UNSIGNED NOT NULL
) ENGINE=Memory;

INSERT INTO tmp_admintech_vtmo_orden (id, orden)
SELECT ordenado.id, ordenado.orden
FROM (
    SELECT
        base.id,
        @admintech_vtmo_orden := IF(@admintech_vtmo_tarea = base.id_tarea, @admintech_vtmo_orden + 1, 1) AS orden,
        @admintech_vtmo_tarea := base.id_tarea
    FROM (
        SELECT id, id_tarea
        FROM visita_tarea_mano_obra
        ORDER BY id_tarea ASC, id ASC
    ) AS base
    CROSS JOIN (SELECT @admintech_vtmo_orden := 0, @admintech_vtmo_tarea := 0) AS vars
) AS ordenado;

UPDATE visita_tarea_mano_obra AS vtmo
JOIN tmp_admintech_vtmo_orden AS tmp ON tmp.id = vtmo.id
SET vtmo.orden = tmp.orden
WHERE vtmo.orden = 0;

DROP TEMPORARY TABLE tmp_admintech_vtmo_orden;

CREATE TEMPORARY TABLE tmp_admintech_ptm_orden (
    id_ptm INT UNSIGNED NOT NULL PRIMARY KEY,
    orden INT UNSIGNED NOT NULL
) ENGINE=Memory;

INSERT INTO tmp_admintech_ptm_orden (id_ptm, orden)
SELECT ordenado.id_ptm, ordenado.orden
FROM (
    SELECT
        base.id_ptm,
        @admintech_ptm_orden := IF(@admintech_ptm_tarea = base.id_presu_tarea, @admintech_ptm_orden + 1, 1) AS orden,
        @admintech_ptm_tarea := base.id_presu_tarea
    FROM (
        SELECT id_ptm, id_presu_tarea
        FROM presupuesto_tarea_material
        ORDER BY id_presu_tarea ASC, id_ptm ASC
    ) AS base
    CROSS JOIN (SELECT @admintech_ptm_orden := 0, @admintech_ptm_tarea := 0) AS vars
) AS ordenado;

UPDATE presupuesto_tarea_material AS ptm
JOIN tmp_admintech_ptm_orden AS tmp ON tmp.id_ptm = ptm.id_ptm
SET ptm.orden = tmp.orden
WHERE ptm.orden = 0;

DROP TEMPORARY TABLE tmp_admintech_ptm_orden;

CREATE TEMPORARY TABLE tmp_admintech_ptmo_orden (
    id_ptmo INT UNSIGNED NOT NULL PRIMARY KEY,
    orden INT UNSIGNED NOT NULL
) ENGINE=Memory;

INSERT INTO tmp_admintech_ptmo_orden (id_ptmo, orden)
SELECT ordenado.id_ptmo, ordenado.orden
FROM (
    SELECT
        base.id_ptmo,
        @admintech_ptmo_orden := IF(@admintech_ptmo_tarea = base.id_presu_tarea, @admintech_ptmo_orden + 1, 1) AS orden,
        @admintech_ptmo_tarea := base.id_presu_tarea
    FROM (
        SELECT id_ptmo, id_presu_tarea
        FROM presupuesto_tarea_mano_obra
        ORDER BY id_presu_tarea ASC, id_ptmo ASC
    ) AS base
    CROSS JOIN (SELECT @admintech_ptmo_orden := 0, @admintech_ptmo_tarea := 0) AS vars
) AS ordenado;

UPDATE presupuesto_tarea_mano_obra AS ptmo
JOIN tmp_admintech_ptmo_orden AS tmp ON tmp.id_ptmo = ptmo.id_ptmo
SET ptmo.orden = tmp.orden
WHERE ptmo.orden = 0;

DROP TEMPORARY TABLE tmp_admintech_ptmo_orden;

CREATE TEMPORARY TABLE tmp_admintech_visita_tareas_nro (
    id_visita INT NOT NULL,
    id_tarea INT NOT NULL PRIMARY KEY,
    nro INT UNSIGNED NOT NULL,
    KEY idx_tmp_admintech_visita_tareas_nro (id_visita, nro)
) ENGINE=Memory;

INSERT INTO tmp_admintech_visita_tareas_nro (id_visita, id_tarea, nro)
SELECT ordenado.id_visita, ordenado.id_tarea, ordenado.nro
FROM (
    SELECT
        base.id_visita,
        base.id_tarea,
        @admintech_tarea_nro := IF(@admintech_tarea_visita = base.id_visita, @admintech_tarea_nro + 1, 1) AS nro,
        @admintech_tarea_visita := base.id_visita
    FROM (
        SELECT id_visita, id_tarea
        FROM visita_tarea
        ORDER BY id_visita ASC, id_tarea ASC
    ) AS base
    CROSS JOIN (SELECT @admintech_tarea_nro := 0, @admintech_tarea_visita := 0) AS vars
) AS ordenado;

UPDATE presupuesto_tarea_material AS ptm
JOIN presupuesto_tareas AS pt ON pt.id_presu_tarea = ptm.id_presu_tarea
JOIN presupuestos AS p ON p.id_presupuesto = pt.id_presupuesto
JOIN tmp_admintech_visita_tareas_nro AS vtn
  ON vtn.id_visita = p.id_previsita
 AND vtn.nro = pt.nro
JOIN visita_tarea_material AS vtm
  ON vtm.id_tarea = vtn.id_tarea
 AND vtm.id_material = ptm.id_material
SET ptm.orden = vtm.orden
WHERE vtm.orden > 0;

UPDATE presupuesto_tarea_mano_obra AS ptmo
JOIN presupuesto_tareas AS pt ON pt.id_presu_tarea = ptmo.id_presu_tarea
JOIN presupuestos AS p ON p.id_presupuesto = pt.id_presupuesto
JOIN tmp_admintech_visita_tareas_nro AS vtn
  ON vtn.id_visita = p.id_previsita
 AND vtn.nro = pt.nro
JOIN visita_tarea_mano_obra AS vtmo
  ON vtmo.id_tarea = vtn.id_tarea
 AND vtmo.id_jornal = ptmo.id_jornal
SET
    ptmo.orden = vtmo.orden,
    ptmo.dias = COALESCE(NULLIF(vtmo.dias, 0), ptmo.dias),
    ptmo.subtotal_fila = ROUND(
        ptmo.cantidad
        * COALESCE(NULLIF(vtmo.dias, 0), ptmo.dias)
        * ptmo.valor_jornal_usado
        * (1 + (ptmo.porcentaje_extra / 100)),
        2
    )
WHERE vtmo.orden > 0;

DROP TEMPORARY TABLE tmp_admintech_visita_tareas_nro;

UPDATE presupuesto_tarea_mano_obra
SET subtotal_fila = ROUND(
    cantidad
    * COALESCE(NULLIF(dias, 0), 1)
    * valor_jornal_usado
    * (1 + (porcentaje_extra / 100)),
    2
);

UPDATE presupuesto_tareas AS pt
LEFT JOIN (
    SELECT id_presu_tarea, SUM(subtotal_fila) AS suma
    FROM presupuesto_tarea_material
    GROUP BY id_presu_tarea
) AS mat ON mat.id_presu_tarea = pt.id_presu_tarea
LEFT JOIN (
    SELECT id_presu_tarea, SUM(subtotal_fila) AS suma
    FROM presupuesto_tarea_mano_obra
    GROUP BY id_presu_tarea
) AS mo ON mo.id_presu_tarea = pt.id_presu_tarea
SET
    pt.suma_mat_filas = COALESCE(mat.suma, 0),
    pt.suma_mo_filas = COALESCE(mo.suma, 0),
    pt.util_mat_contable = CASE
        WHEN pt.utilidad_materiales_pct IS NULL THEN 0
        ELSE COALESCE(mat.suma, 0) * (pt.utilidad_materiales_pct / 100)
    END,
    pt.util_mo_contable = CASE
        WHEN pt.utilidad_mano_obra_pct IS NULL THEN 0
        ELSE COALESCE(mo.suma, 0) * (pt.utilidad_mano_obra_pct / 100)
    END,
    pt.total_base =
        COALESCE(mat.suma, 0)
        + COALESCE(mo.suma, 0)
        + CASE
            WHEN pt.utilidad_materiales_pct IS NULL THEN 0
            ELSE COALESCE(mat.suma, 0) * (pt.utilidad_materiales_pct / 100)
          END
        + CASE
            WHEN pt.utilidad_mano_obra_pct IS NULL THEN 0
            ELSE COALESCE(mo.suma, 0) * (pt.utilidad_mano_obra_pct / 100)
          END,
    pt.total_mostrado =
        COALESCE(mat.suma, 0)
        + COALESCE(mo.suma, 0)
        + CASE
            WHEN pt.utilidad_materiales_pct IS NULL THEN 0
            ELSE COALESCE(mat.suma, 0) * (pt.utilidad_materiales_pct / 100)
          END
        + CASE
            WHEN pt.utilidad_mano_obra_pct IS NULL THEN 0
            ELSE COALESCE(mo.suma, 0) * (pt.utilidad_mano_obra_pct / 100)
          END
        + pt.otros_materiales_monto
        + pt.otros_mano_obra_monto;

UPDATE presupuestos AS p
LEFT JOIN (
    SELECT
        id_presupuesto,
        SUM(COALESCE(total_base, 0)) AS total_base,
        SUM(COALESCE(total_mostrado, 0)) AS total_mostrado
    FROM presupuesto_tareas
    WHERE incluir_en_total = 1
    GROUP BY id_presupuesto
) AS tot ON tot.id_presupuesto = p.id_presupuesto
SET
    p.total_base = COALESCE(tot.total_base, 0),
    p.total_mostrado = COALESCE(tot.total_mostrado, 0);

CALL admintech_add_index_if_missing(
    'visita_tarea_material',
    'idx_visita_tarea_material_orden',
    '(`id_tarea`, `orden`, `id`)'
);

CALL admintech_add_index_if_missing(
    'visita_tarea_mano_obra',
    'idx_visita_tarea_mano_obra_orden',
    '(`id_tarea`, `orden`, `id`)'
);

CALL admintech_add_index_if_missing(
    'presupuesto_tarea_material',
    'idx_presupuesto_tarea_material_orden',
    '(`id_presu_tarea`, `orden`, `id_ptm`)'
);

CALL admintech_add_index_if_missing(
    'presupuesto_tarea_mano_obra',
    'idx_presupuesto_tarea_mano_obra_orden',
    '(`id_presu_tarea`, `orden`, `id_ptmo`)'
);

DROP PROCEDURE IF EXISTS admintech_add_column_if_missing;
DROP PROCEDURE IF EXISTS admintech_add_index_if_missing;
