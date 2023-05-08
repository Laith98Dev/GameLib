-- # !sqlite
-- # { gl-queries
-- # { init
CREATE TABLE IF NOT EXISTS arenas(
	arenaID VARCHAR NOT NULL PRIMARY KEY,
	worldName VARCHAR NOT NULL,
	mode VARCHAR NOT NULL,
	maxPlayersPerTeam INTEGER NOT NULL,
	waitingLobbySettings VARCHAR,
	spawns VARCHAR
);
-- # }
-- # { add-arena
-- # 	:arenaID string
-- # 	:worldName string
-- # 	:mode string
-- # 	:maxPlayersPerTeam int
INSERT INTO arenas(arenaID, worldName, mode, maxPlayersPerTeam) VALUES (:arenaID, :worldName, :mode, :maxPlayersPerTeam);
-- # }
-- # { set-arena-spawns
-- #	:arenaID string
-- #	:spawns string
UPDATE arenas SET spawns = :spawns WHERE arenaID = :arenaID;
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