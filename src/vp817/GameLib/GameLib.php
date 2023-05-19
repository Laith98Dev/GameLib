<?php

/**
 *   .oooooo.                                          ooooo         o8o   .o8
 *  d8P'  `Y8b                                         `888'         `"'  "888
 * 888            .oooo.   ooo. .oo.  .oo.    .ooooo.   888         oooo   888oooo.
 * 888           `P  )88b  `888P"Y88bP"Y88b  d88' `88b  888         `888   d88' `88b
 * 888     ooooo  .oP"888   888   888   888  888ooo888  888          888   888   888
 * `88.    .88'  d8(  888   888   888   888  888    .o  888       o  888   888   888
 *  `Y8bood8P'   `Y888""8o o888o o888o o888o `Y8bod8P' o888ooooood8 o888o  `Y8bod8P'
 * 
 * @author vp817, Laith98Dev
 * 
 * Copyright (C) 2023  vp817
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace vp817\GameLib;

use Closure;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLogger;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\world\WorldManager;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\ArenaDataParser;
use vp817\GameLib\arena\message\ArenaMessages;
use vp817\GameLib\arena\message\DefaultArenaMessages;
use vp817\GameLib\managers\ArenasManager;
use vp817\GameLib\managers\SetupManager;
use vp817\GameLib\player\SetupPlayer;
use vp817\GameLib\utilities\SqlQueries;
use vp817\GameLib\utilities\Utils;
use function basename;
use function count;
use function mkdir;
use function is_dir;
use function json_encode;
use function is_null;
use function strtolower;
use function realpath;
use const DIRECTORY_SEPARATOR;

final class GameLib
{

	/** @var null|PluginBase $plugin */
	private static ?PluginBase $plugin = null;
	/** @var ?DataConnector $plugin */
	private static ?DataConnector $database = null;
	/** @var ArenasManager $arenasManager */
	private ArenasManager $arenasManager;
	/** @var ArenaMessages $arenaMessages */
	private ArenaMessages $arenaMessages;
	/** @var string $arenasBackupPath */
	private string $arenasBackupPath;
	/** @var SetupManager $setupManager */
	private SetupManager $setupManager;

	/**
	 * initialize a new gamelib
	 * 
	 * sqlDatabase usage example:
	 * 
	 * sqlite: [ "type" => "sqlite" ]
	 * 
	 * mysql: [
	 * 		"type" => "mysql",
	 * 		"host" => "127.0.0.1",
	 * 		"username" => "root",
	 * 		"password" => "",
	 * 		"schema" => "schema"
	 * ]
	 * 
	 * @param PluginBase $plugin
	 * @param array $sqlDatabase
	 * @return GameLib
	 * @throws RuntimeException
	 */
	public static function init(PluginBase $plugin, array $sqlDatabase): GameLib
	{
		if (self::$plugin !== null) {
			throw new RuntimeException("GameLib is already initialized for this plugin");
		}

		if (!class_exists("poggit\libasynql\libasynql")) {
			throw new RuntimeException("libasyql virion not found. unable to use gamelib");
		}

		return new GameLib($plugin, $sqlDatabase);
	}

	/**
	 * @return void
	 */
	public static function uninit(): void
	{
		if (isset(self::$database)) {
			self::$database->close();
		}
	}

	/**
	 * @param PluginBase $plugin
	 * @param array $sqlDatabase
	 */
	public function __construct(PluginBase $plugin, array $sqlDatabase = [])
	{
		self::$plugin = $plugin;

		$sqlType = $sqlDatabase["type"];
		$database = [
			"type" => $sqlType
		];

		if ($sqlType == "sqlite") {
			$database["sqlite"] = [
				"file" => "data.sql"
			];
		} else if ($sqlType == "mysql") {
			$database["mysql"] = [
				"host" => $sqlDatabase["host"],
				"username" => $sqlDatabase["username"],
				"password" => $sqlDatabase["password"],
				"schema" => $sqlDatabase["schema"]
			];
		}

		$sqlMapPath = $plugin->getDataFolder() . "SqlMap";
		if (!is_dir($sqlMapPath)) {
			@mkdir($sqlMapPath);
		}

		foreach (glob(Path::join($this->getResourcesPath(), "*.sql")) as $resource) {
			$filename = basename($resource);
			Utils::saveResourceToPlugin($plugin, $this->getResourcesPath(), $filename, $sqlMapPath);
		}

		self::$database = Utils::libasynqlCreateForVirion($plugin, $database, [
			"sqlite" => Path::join($sqlMapPath, "sqlite.sql"),
			"mysql" => Path::join($sqlMapPath, "mysql.sql")
		]);

		self::$database->executeGeneric(SqlQueries::INIT, [], null, static function (SqlError $error) use ($plugin): void {
			$plugin->getLogger()->error($error->getMessage());
		});
	
		self::$database->waitAll();

		$this->arenasManager = new ArenasManager();
		$this->arenaMessages = new DefaultArenaMessages();
		$this->setupManager = new SetupManager();
	}

	/**
	 * @return string
	 */
	public function getResourcesPath(): string
	{
		return realpath(__DIR__ . "/../../../resources") . DIRECTORY_SEPARATOR;
	}

	/**
	 * @param string $path
	 * @return void
	 */
	public function setArenasBackupPath(string $path): void
	{
		if (!is_dir($path)) {
			@mkdir($path);
		}

		$this->arenasBackupPath = $path . DIRECTORY_SEPARATOR;
	}

	/**
	 * @internal
	 * @return WorldManager
	 */
	public function getWorldManager(): WorldManager
	{
		return self::$plugin->getServer()->getWorldManager();
	}

	/**
	 * @internal
	 * @return TaskScheduler
	 */
	public function getScheduler(): TaskScheduler
	{
		return self::$plugin->getScheduler();
	}

	/**
	 * @internal
	 * @return PluginLogger
	 */
	public function getLogger(): PluginLogger
	{
		return self::$plugin->getLogger();
	}

	/**
	 * @param ArenaMessages $arenaMessages
	 * @return void
	 */
	public function setArenaMessagesClass(ArenaMessages $arenaMessages): void
	{
		$this->arenaMessages = $arenaMessages;
	}

	/**
	 * @return ArenasManager
	 */
	public function getArenasManager(): ArenasManager
	{
		return $this->arenasManager;
	}

	/**
	 * @return string
	 */
	public function getArenasBackupPath(): string
	{
		return $this->arenasBackupPath;
	}

	/**
	 * @return ArenaMessages
	 */
	public function getArenaMessagesClass(): ArenaMessages
	{
		return $this->arenaMessages;
	}

	/**
	 * @return SetupManager
	 */
	public function getSetupManager(): SetupManager
	{
		return $this->setupManager;
	}

	/**
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function loadArenas(?callable $onSuccess = null, ?callable $onFail = null): void
	{
		self::$database->executeSelect(SqlQueries::GET_ALL_ARENAS, [], function ($rows) use ($onSuccess, $onFail): void {
			if (count($rows) < 1) {
				if (!is_null($onFail)) {
					$onFail("None", "no arenas to be loaded");
				}
				return;
			}
			foreach ($rows as $arenasData) {
				$arenaID = $arenasData["arenaID"];
				$this->arenaExistsInDB($arenaID, function (bool $arenaExists) use ($arenaID, $arenasData, $onSuccess, $onFail): void {
					if ($arenaExists || $this->getArenasManager()->hasLoadedArena($arenaID)) {
						if (!is_null($onFail)) {
							$onFail($arenaID, "Arena already exists");
						}
						return;
					}

					$this->getArenasManager()->signAsLoaded($arenaID, new Arena($this, new ArenaDataParser($arenasData)), function ($arenaID, $arena) use ($onSuccess): void {
						if (!is_null($onSuccess)) {
							$onSuccess($arenaID, $arena);
						}
					});
				});
			}
		});
	}

	/**
	 * @param string $arenaID
	 * @param string $worldName
	 * @param string $mode
	 * @param int $countdownTime
	 * @param int $arenaTime
	 * @param int $restartingTime
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function createArena(string $arenaID, string $worldName, string $mode, int $countdownTime, int $arenaTime, int $restartingTime, ?callable $onSuccess = null, ?callable $onFail = null): void
	{
		if ($this->getArenasManager()->hasLoadedArena($arenaID)) {
			$reason = "Arena is already loaded";
			if (!is_null($onFail)) {
				$onFail($arenaID, $reason);
				return;
			}
			throw new RuntimeException($reason);
		}

		$this->arenaExistsInDB($arenaID, function (bool $arenaExists) use ($arenaID, $worldName, $mode, $countdownTime, $arenaTime, $restartingTime, $onSuccess, $onFail): void {
			if ($arenaExists) {
				if (!is_null($onFail)) {
					$onFail($arenaID, "Arena already exists");
				}
				return;
			}

			$data = [
				"arenaID" => $arenaID,
				"worldName" => $worldName,
				"mode" => $mode,
				"countdownTime" => $countdownTime,
				"arenaTime" => $arenaTime,
				"restartingTime" => $restartingTime
			];

			self::$database->executeInsert(SqlQueries::ADD_ARENA, $data);

			$data["lobbySettings"] = json_encode([]);
			$data["spawns"] = json_encode([]);
			$data["extraData"] = json_encode([]);

			$this->getArenasManager()->signAsLoaded($arenaID, new Arena($this, new ArenaDataParser($data)), function ($arenaID, $arena) use ($onSuccess): void {
				if (!is_null($onSuccess)) {
					$onSuccess($arenaID, $arena);
				}
			});
		});
	}

	/**
	 * @param string $arenaID
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @param bool $alertConsole
	 * @return void
	 */
	public function removeArena(string $arenaID, ?callable $onSuccess = null, ?callable $onFail = null, bool $alertConsole = true): void
	{
		$this->arenaExistsInDB($arenaID, function($arenaExists) use ($arenaID, $onSuccess, $onFail, $alertConsole): void {
			if (!$arenaExists && !$this->getArenasManager()->hasLoadedArena($arenaID)) {
				$reason = "Arena does not exists";
				if (!is_null($onFail)) {
					$onFail($arenaID, $reason);
				}
				return;
			}

			self::$database->executeChange(SqlQueries::REMOVE_ARENA, ["arenaID" => $arenaID], function ($rows) use ($arenaID, $onSuccess, $alertConsole): void {
				$this->getArenasManager()->unsignFromBeingLoaded($arenaID, function () use ($arenaID, $onSuccess): void {
					if (!is_null($onSuccess)) {
						$onSuccess($arenaID);
					}
				});

				if ($alertConsole) {
					self::$plugin->getLogger()->alert("Arena: $arenaID has been successfully removed");
				}
			});
		});
	}

	/**
	 * @param string $arenaID
	 * @return void
	 */
	public function arenaExistsInDB(string $arenaID, callable $valueCallback): void
	{
		self::$database->executeSelect(SqlQueries::GET_ALL_ARENAS, [], function ($rows) use ($valueCallback, $arenaID): void {
			if (count($rows) > 0) {
				$valueCallback(false);
				return;
			}
			foreach ($rows as $arenasData) {
				if (strtolower($arenasData["arenaID"]) === strtolower($arenaID)) {
					$valueCallback(true);
					return;
				}
			}
			$valueCallback(false);
		});
	}

	/**
	 * @param Player $player
	 * @param string $arenaID
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function addPlayerToSetupArena(Player $player, string $arenaID, ?callable $onSuccess = null, ?callable $onFail = null): void
	{
		if (!$this->getArenasManager()->hasLoadedArena($arenaID)) {
			if (!is_null($onFail)) {
				$onFail($arenaID, "Arena does not exists");
			}
			return;
		}

		$this->getSetupManager()->add($player, $arenaID, function(SetupPlayer $player) use ($onSuccess): void {
			if (!is_null($onSuccess)) {
				$onSuccess($player);
			}
		}, function() use ($arenaID, $onFail): void {
			if (!is_null($onFail)) {
				$onFail($arenaID, "You are already inside the setup");
			}
		});
	}

	/**
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function finishArenaSetup(Player $player, ?callable $onSuccess = null, ?callable $onFail = null): void
	{
		$setupManager = $this->getSetupManager();
		if (!$setupManager->has($player->getUniqueId()->getBytes())) {
			if (!is_null($onFail)) {
				$onFail("The player is not inside the setup");
			}
			return;
		}

		$setupManager->get($player->getUniqueId()->getBytes(), function (SetupPlayer $setupPlayer) use ($setupManager, $onSuccess, $onFail): void {
			$setupSettings = $setupPlayer->getSetupSettings();
			$arenaID = $setupPlayer->getSetuppingArenaID();

			$fail = function (SqlError $error) use ($onFail): void {
				if (!is_null($onFail)) {
					$onFail($error->getMessage());
				}
			};

			self::$database->executeChange(SqlQueries::UPDATE_ARENA_SPAWNS, ["arenaID" => $arenaID, "spawns" => json_encode($setupSettings->getSpawns())], null, $fail);
			self::$database->executeChange(SqlQueries::UPDATE_ARENA_LOBBY_SETTINGS, ["arenaID" => $arenaID, "settings" => $setupSettings->getLobbySettings()], null, $fail);
			self::$database->executeChange(SqlQueries::UPDATE_ARENA_DATA, ["arenaID" => $arenaID, "arenaData" => $setupSettings->getArenaData()], null, $fail);

			if ($setupSettings->hasExtraData()) {
				self::$database->executeChange(SqlQueries::UPDATE_ARENA_EXTRA_DATA, ["arenaID" => $arenaID, "extraData" => $setupSettings->getExtraData()], null, $fail);
			}

			$setupSettings->clear();
			$setupManager->remove($setupPlayer->getCells());

			if (!is_null($onSuccess)) {
				$onSuccess();
			}
		});
	}
}
