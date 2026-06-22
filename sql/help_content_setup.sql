-- Help content table setup
-- Run once to create the table and seed pilot content.

CREATE TABLE IF NOT EXISTS `help_content` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `page_id`           int(11)      NOT NULL,
  `title`             varchar(255) NOT NULL DEFAULT '',
  `content`           text         NOT NULL,
  `date_registered`   datetime     DEFAULT NULL,
  `registered_by`     int(11)      DEFAULT NULL,
  `date_last_updated` datetime     DEFAULT NULL,
  `modified_by`       int(11)      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pilot content: Client Admin (page 331)
INSERT INTO `help_content` (`page_id`, `title`, `content`, `date_registered`, `registered_by`, `date_last_updated`, `modified_by`)
VALUES (
  331,
  'Client Administration',
  'The Client Administration page lets you manage client records.\n\nTo edit a client, select their name from the dropdown at the top of the form, then click Edit. Change any details and click Save.\n\nTo add a new client, click New, fill in the required fields (marked with *), and click Save.\n\nTo delete a client, select them, click Edit, then click Delete. A confirmation will be shown before the record is removed.',
  NOW(), 1, NOW(), 1
);

-- Pilot content: Client Vols (page 334)
INSERT INTO `help_content` (`page_id`, `title`, `content`, `date_registered`, `registered_by`, `date_last_updated`, `modified_by`)
VALUES (
  334,
  'Client Check-In',
  'Use this page to record client attendance at the current session.\n\nFind the client in the list and click Check In. If the client has members (dependants), they will be shown and can be included in the attendance.\n\nIf a client attends twice within 7 days a note will be automatically appended to their record for review.\n\nSigning: if a signature pad is connected, the client can sign directly on screen.',
  NOW(), 1, NOW(), 1
);

-- Pilot content: Roster (page 101)
INSERT INTO `help_content` (`page_id`, `title`, `content`, `date_registered`, `registered_by`, `date_last_updated`, `modified_by`)
VALUES (
  101,
  'Volunteer Roster',
  'The Roster page shows upcoming sessions and lets you manage your bookings.\n\nTo book yourself onto a session, click the session date and choose Book. To cancel a booking, choose Cancel.\n\nAdministrators can also book other volunteers onto sessions using the volunteer selector that appears at the top of the form.',
  NOW(), 1, NOW(), 1
);

-- Pilot content: Stock Reports (page 406)
INSERT INTO `help_content` (`page_id`, `title`, `content`, `date_registered`, `registered_by`, `date_last_updated`, `modified_by`)
VALUES (
  406,
  'Stock Reports',
  'The Stock Reports page gives you several views of stock data.\n\nStock Levels: shows the current quantity of each item across all locations. Use the Location filter to narrow the view. The As At field lets you see stock levels as they were at a past date and time.\n\nBelow Minimum: lists items whose current quantity is below the minimum level set for that item.\n\nStock Usage: shows how much of each item has been distributed over a date range.\n\nStocktake Variance: compares a stocktake count against expected quantities.\n\nDeliveries: summarises incoming deliveries over a date range, optionally filtered by supplier or category.\n\nAll reports can be exported to CSV using the Download button.',
  NOW(), 1, NOW(), 1
);
