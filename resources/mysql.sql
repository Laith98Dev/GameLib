-- # }
-- # !mysql
-- # { gl-queries
-- # { init
CREATE TABLE IF NOT EXISTS arenas(
	arenaID VARCHAR(65535) NOT NULL PRIMARY KEY,
	worldName VARCHAR(65535) NOT NULL,
	waitingLobbyWorldName VARCHAR(65535) NOT NULL,
	mode VARCHAR(65535) NOT NULL,
	maxPlayersPerTeam INTEGER NOT NULL,
	spawns VARCHAR(65535)
);
-- # }
-- # { add-arena
-- # 	:arenaID string
-- # 	:worldName string
-- # 	:waitingLobbyWorldName string
-- # 	:mode string
-- # 	:maxPlayersPerTeam int
INSERT INTO arenas(arenaID, worldName, waitingLobbyWorldName, mode, maxPlayersPerTeam) VALUES (:arenaID, :worldName, :waitingLobbyWorldName, :mode, :maxPlayersPerTeam);
-- # }
-- # { add-arena-spawn
-- #	:arenaID string
-- #	:spawns string
-- TODO: CHECK THOSE (ADD ARENA SPAWN + REMOVE ARENA)
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
-- # }