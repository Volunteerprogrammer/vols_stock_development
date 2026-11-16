-- Supplies stock-event page (page 417, event type 'issue').
-- Adds the page, its Access action, the same role access as the Adjustment page (414),
-- and menu entries alongside Adjustment in both stock-event menus.
-- Safe to re-run: each insert is skipped if the row already exists.
-- Run on each environment (local and production) - sql/*.sql is not deployed.

-- 1. Page row
INSERT INTO page (pagenumber, name, pagetype, unrestricted, maxcolumns, autoextendtasks)
SELECT 417, 'Stock Supplies', 0, 0, 0, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM page WHERE pagenumber = 417);

SET @supplies_page_id   = (SELECT id FROM page WHERE pagenumber = 417);
SET @adjustment_page_id = (SELECT id FROM page WHERE pagenumber = 414);
SET @access_action_id   = (SELECT id FROM action WHERE code = 'VIEW');

-- 2. "Access" page action for the new page
INSERT INTO page_action (page_id, action_id)
SELECT @supplies_page_id, @access_action_id
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM page_action WHERE page_id = @supplies_page_id AND action_id = @access_action_id);

SET @supplies_pa_id   = (SELECT id FROM page_action WHERE page_id = @supplies_page_id   AND action_id = @access_action_id);
SET @adjustment_pa_id = (SELECT id FROM page_action WHERE page_id = @adjustment_page_id AND action_id = @access_action_id);

-- 3. Give every role that can access Adjustments access to Supplies
INSERT INTO role_pageaction (role_id, pageaction_id)
SELECT rp.role_id, @supplies_pa_id
FROM role_pageaction rp
WHERE rp.pageaction_id = @adjustment_pa_id
  AND NOT EXISTS (SELECT 1 FROM role_pageaction x WHERE x.role_id = rp.role_id AND x.pageaction_id = @supplies_pa_id);

-- 4. Menu entries (after Adjustment in the main Stock Events submenu and in menu 2)
INSERT INTO menuitem (menucode, page_number, text, inactive, menu_number, is_public)
SELECT '07_05', '417', 'Supplies', 0, 0, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menuitem WHERE menucode = '07_05');

INSERT INTO menuitem (menucode, page_number, text, inactive, menu_number, is_public)
SELECT '35', '417', 'Supplies', 0, 2, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menuitem WHERE menucode = '35');
