-- # !sqlite
-- # { gl-queries
-- # { init
CREATE TABLE IF NOT EXISTS arenas(
	arenaID VARCHAR NOT NULL PRIMARY KEY,
	worldName VARCHAR NOT NULL,
	mode VARCHAR NOT NULL,
	countdownTime INTEGER NOT NULL,
	arenaTime INTEGER NOT NULL,
	restartTime INTEGER NOT NULL,
	lobbySettings VARCHAR,
	spawns VARCHAR,
	arenaData VARCHAR,
	extraData VARCHAR
);
-- # }
-- # { add-arena
-- # 	:arenaID string
-- # 	:worldName string
-- # 	:mode string
-- #	:countdownTime int
-- #	:arenaTime int
-- #	:restartTime int
INSERT INTO arenas(arenaID, worldName, mode, countdownTime, arenaTime, restartTime) VALUES (:arenaID, :worldName, :mode, :countdownTime, :arenaTime, :restartTime);
-- # }
-- # { update-arena-spawns
-- #	:arenaID string
-- #	:spawns string
UPDATE arenas SET spawns = :spawns WHERE arenaID = :arenaID;
-- # }
-- # { update-arena-lobby-settings
-- #	:arenaID string
-- #	:settings string
UPDATE arenas SET lobbySettings = :settings WHERE arenaID = :arenaID;
-- # }
-- # { update-arena-data
-- #	:arenaID string
-- #	:arenaData string
UPDATE arenas SET arenaData = :arenaData WHERE arenaID = :arenaID;
-- # }
-- # { update-arena-extra-data
-- #	:arenaID string
-- #	:extraData string
UPDATE arenas SET extraData = :extraData WHERE arenaID = :arenaID;
-- # }
-- # { remove-arena
-- #	:arenaID string
DELETE FROM arenas WHERE arenaID = :arenaID;
-- # }
-- # { get-arena-data
-- #	:arenaID string
SELECT * FROM arenas WHERE arenaID = :arenaID;
-- # }
-- # { get-all-arenas
SELECT * FROM arenas;
-- # }
-- # }
