-- # !mysql
-- # { gl-queries
-- # { init
CREATE TABLE IF NOT EXISTS arenas(
	arenaID VARCHAR(65535) NOT NULL PRIMARY KEY,
	worldName VARCHAR(65535) NOT NULL,
	mode VARCHAR(65535) NOT NULL,
	maxPlayersPerTeam INTEGER NOT NULL,
	lobbySettings VARCHAR(65535),
	spawns VARCHAR(65535),
	extaData VARCHAR(65535)
);
-- # }
-- # { add-arena
-- # 	:arenaID string
-- # 	:worldName string
-- # 	:mode string
-- # 	:maxPlayersPerTeam int
INSERT INTO arenas(arenaID, worldName, mode, maxPlayersPerTeam) VALUES (:arenaID, :worldName, :mode, :maxPlayersPerTeam);
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
-- # { update-arena-exta-data
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
