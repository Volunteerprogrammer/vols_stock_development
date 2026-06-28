-- Create roster entity (id matches parent page.id — no AUTO_INCREMENT)
CREATE TABLE IF NOT EXISTS roster (
    id                INT NOT NULL,
    name              VARCHAR(255) NOT NULL DEFAULT '',
    maxcolumns        INT NOT NULL DEFAULT 0,
    autoextendtasks   TINYINT NOT NULL DEFAULT 0,
    leadtime          INT NOT NULL DEFAULT 0,
    publishedleadtime INT NOT NULL DEFAULT 0,
    startdate         DATE NULL,
    enddate           DATE NULL,
    sessiondepth      INT NOT NULL DEFAULT 12,
    PRIMARY KEY (id),
    CONSTRAINT fk_roster_page FOREIGN KEY (id) REFERENCES page(id) ON DELETE CASCADE
);

-- Populate from existing roster pages; aggregate task fields with MIN/MAX
INSERT INTO roster (id, name, maxcolumns, autoextendtasks, leadtime, publishedleadtime, startdate, enddate, sessiondepth)
SELECT
    p.id,
    p.name,
    COALESCE(p.maxcolumns, 0),
    COALESCE(p.autoextendtasks, 0),
    COALESCE(MIN(t.leadtime), 0),
    COALESCE(MIN(t.publishedleadtime), 0),
    MIN(t.startdate),
    MAX(t.enddate),
    COALESCE(MIN(t.sessiondepth), 12)
FROM page p
LEFT JOIN task t ON t.page_id = p.id
WHERE p.pagetype = '2'
GROUP BY p.id, p.name, p.maxcolumns, p.autoextendtasks;

-- Remove migrated fields from page
ALTER TABLE page
    DROP COLUMN maxcolumns,
    DROP COLUMN autoextendtasks;

-- Remove migrated fields from task
ALTER TABLE task
    DROP COLUMN leadtime,
    DROP COLUMN publishedleadtime,
    DROP COLUMN startdate,
    DROP COLUMN enddate,
    DROP COLUMN sessiondepth;
