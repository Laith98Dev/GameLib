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
use Phar;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLogger;
use pocketmine\scheduler\AsyncPool;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\world\WorldManager;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use TypeError;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\ArenaDataParser;
use vp817\GameLib\arena\message\ArenaMessages;
use vp817\GameLib\arena\message\DefaultArenaMessages;
use vp817\GameLib\arena\states\ArenaStates;
use vp817\GameLib\event\listener\DefaultArenaListener;
use vp817\GameLib\event\listener\ServerEventListener;
use vp817\GameLib\managers\ArenasManager;
use vp817\GameLib\managers\SetupManager;
use vp817\GameLib\player\SetupPlayer;
use vp817\GameLib\tasks\async\CreateZipAsyncTask;
use vp817\GameLib\utils\SqlQueries;
use vp817\GameLib\utils\Utils;
use const DIRECTORY_SEPARATOR;
use function array_filter;
use function array_key_exists;
use function array_key_last;
use function array_rand;
use function array_shift;
use function basename;
use function class_exists;
use function count;
use function file_exists;
use function in_array;
use function is_dir;
use function is_null;
use function json_encode;
use function ksort;
use function mkdir;
use function shuffle;
use function strlen;
use function strtolower;
use function trim;

final class GameLib
{

	private static ?PluginBase $plugin = null;
	private static ?DataConnector $database = null;
	private ArenasManager $arenasManager;
	private ArenaMessages $arenaMessages;
	private string $arenaListenerClass;
	private string $arenasBackupPath;
	private SetupManager $setupManager;

	/**
	 * @return bool
	 */
	protected static function isPhar(): bool
	{
		return Phar::running() !== "";
	}

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
		if (!is_null(self::$plugin)) {
			throw new RuntimeException("GameLib is already initialized for this plugin");
		}

		if (!class_exists("poggit\libasynql\libasynql")) {
			throw new RuntimeException("libasyql virion not found. unable to use gamelib");
		}

		$autoload = self::isPhar() ? "phar://" : "";
		$autoload .= __DIR__ . "/../../../vendor/autoload.php";
		if (!file_exists($autoload)) {
			throw new RuntimeException(message: "Composer autoloader for gamelib not found.");
		}

		require_once $autoload;

		return new GameLib(
			plugin: $plugin,
			sqlDatabase: $sqlDatabase
		);
	}

	/**
	 * @return void
	 * @throws GameLib
	 */
	public static function uninit(): void
	{
		if (is_null(self::$plugin)) {
			throw new RuntimeException(message: "There is no instance to uninitialize for gamelib");
		}

		if (isset(self::$database)) {
			self::$database->close();
		}
	}

	/**
	 * @param PluginBase $plugin
	 * @param array $sqlDatabase
	 */
	private function __construct(
		PluginBase $plugin,
		array $sqlDatabase = []
	) {
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
			Utils::saveResourceToPlugin(
				plugin: $plugin,
				resourcePath: $this->getResourcesPath(),
				filename: $filename,
				pathToUploadTo: $sqlMapPath
			);
		}

		self::$database = Utils::libasynqlCreateForVirion(
			plugin: $plugin,
			configData: $database,
			sqlMap: [
				"sqlite" => Path::join($sqlMapPath, "sqlite.sql"),
				"mysql" => Path::join($sqlMapPath, "mysql.sql")
			]
		);

		self::$database->executeGeneric(
			queryName: SqlQueries::INIT,
			args: [],
			onSuccess: null,
			onError: static function (SqlError $error) use ($plugin): void {
				$plugin->getLogger()->error($error->getMessage());
			}
		);

		self::$database->waitAll();

		$this->arenasManager = new ArenasManager();
		$this->arenaMessages = new DefaultArenaMessages();
		$this->setupManager = new SetupManager();
		$this->arenaListenerClass = DefaultArenaListener::class;

		self::$plugin->getServer()->getPluginManager()->registerEvents(
			listener: new ServerEventListener($this),
			plugin: self::$plugin
		);
	}

	/**
	 * @return string
	 */
	public function getResourcesPath(): string
	{
		$path = self::isPhar() ? "phar://" : "";
		$path .= __DIR__ . "/../../../resources/";
		return $path;
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
	 * @return AsyncPool
	 */
	public function getAsyncPool(): AsyncPool
	{
		return self::$plugin->getServer()->getAsyncPool();
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
	 * @param string $arenaListener
	 * @return void
	 */
	public function setArenaListenerClass(string $arenaListener): void
	{
		$this->arenaListenerClass = $arenaListener;
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
	 * @return string
	 */
	public function getServerWorldsPath(): string
	{
		return Path::join(self::$plugin->getServer()->getDataPath(), "worlds");
	}

	/**
	 * @return ArenaMessages
	 */
	public function getArenaMessagesClass(): ArenaMessages
	{
		return $this->arenaMessages;
	}

	/**
	 * @return string
	 */
	public function getArenaListenerClass(): string
	{
		return $this->arenaListenerClass;
	}

	/**
	 * @return SetupManager
	 */
	public function getSetupManager(): SetupManager
	{
		return $this->setupManager;
	}

	/**
	 * @param Arena $arena
	 * @return void
	 * @throws TypeError
	 */
	public function registerArenaListener(Arena $arena): void
	{
		$class = $this->getArenaListenerClass();

		if (strlen(trim($class)) < 1) {
			return;
		}

		$listener = null;
		try {
			$listener = new $class(
				plugin: self::$plugin,
				gamelib: $this,
				arena: $arena
			);
		} catch (TypeError $_) {
			$listener = null;
		}

		if (is_null($listener)) {
			return;
		}

		if (!$listener instanceof DefaultArenaListener) {
			throw new TypeError(message: "The listener that you provided is not an instance of GameLib/DefaultArenaListener.php");
		}

		self::$plugin->getServer()->getPluginManager()->registerEvents(
			listener: $listener,
			plugin: self::$plugin
		);
	}

	/**
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function loadArenas(?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		self::$database->executeSelect(
			queryName: SqlQueries::GET_ALL_ARENAS,
			args: [],
			onSelect: function ($rows) use ($onSuccess, $onFail): void {
				if (count($rows) < 1) {
					if (!is_null($onFail)) $onFail("None", "no arenas to be loaded");
					return;
				}

				foreach ($rows as $arenasData) {
					$arenaID = $arenasData["arenaID"];
					$this->arenaExistsInDatabase(
						arenaID: $arenaID,
						resultClosure: function (bool $arenaExists) use ($arenaID, $arenasData, $onSuccess, $onFail): void {
							if (!$arenaExists) {
								if (!is_null($onFail)) $onFail($arenaID, "Arena doesnt exists in db. this shouldnt happen");
								return;
							}
							if ($this->getArenasManager()->hasLoadedArena($arenaID)) {
								if (!is_null($onFail)) $onFail($arenaID, "unable to load an already loaded arena");
								return;
							}

							$this->getArenasManager()->signAsLoaded(
								arenaID: $arenaID,
								arena: new Arena(
									gamelib: $this,
									dataParser: new ArenaDataParser(
										data: $arenasData
									)
								),
								onSuccess: fn (Arena $arena) => !is_null($onSuccess) ? $onSuccess($arena) : null
							);
						}
					);
				}
			}
		);
	}

	/**
	 * @param string $arenaID
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function loadArena(string $arenaID, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$this->arenaExistsInDatabase(
			arenaID: $arenaID,
			resultClosure: function ($arenaExists) use ($arenaID, $onSuccess, $onFail): void {
				if (!$arenaExists) {
					if (!is_null($onFail)) $onFail();
					return;
				}

				self::$database->executeSelect(
					queryName: SqlQueries::GET_ARENA_DATA,
					args: ["arenaID" => $arenaID],
					onSelect: function ($rows) use ($onSuccess, $onFail): void {
						if (count($rows) < 1) {
							if (!is_null($onFail)) $onFail();
							return;
						}

						foreach ($rows as $arenaData) {
							$arenaID = $arenaData["arenaID"];

							if (!array_key_exists("lobbySettings", $arenaData)) $arenaData["lobbySettings"] = json_encode([]);
							if (!array_key_exists("spawns", $arenaData)) $arenaData["spawns"] = json_encode([]);
							if (!array_key_exists("arenaData", $arenaData)) $arenaData["arenaData"] = json_encode([]);
							if (!array_key_exists("extraData", $arenaData)) $arenaData["extraData"] = json_encode([]);

							$this->getArenasManager()->signAsLoaded(
								arenaID: $arenaID,
								arena: new Arena(
									gamelib: $this,
									dataParser: new ArenaDataParser($arenaData)
								),
								onSuccess: fn (Arena $arena) => !is_null($onSuccess) ? $onSuccess($arena) : null
							);
						}
					}
				);
			}
		);
	}

	/**
	 * @param string $arenaID
	 * @param string $worldName
	 * @param string $mode
	 * @param int $countdownTime
	 * @param int $arenaTime
	 * @param int $restartingTime
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function createArena(string $arenaID, string $worldName, string $mode, int $countdownTime, int $arenaTime, int $restartingTime, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$this->arenaExistsInDatabase(
			arenaID: $arenaID,
			resultClosure: function (bool $arenaExists) use ($arenaID, $worldName, $mode, $countdownTime, $arenaTime, $restartingTime, $onSuccess, $onFail): void {
				if ($arenaExists) {
					if (!is_null($onFail)) $onFail($arenaID, "Arena already exists");
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

				self::$database->executeInsert(
					queryName: SqlQueries::ADD_ARENA,
					args: $data
				);

				$zipFileFullPath = Path::join($this->getArenasBackupPath(), $arenaID) . ".zip";
				$worldManager = $this->getWorldManager();

				if ($worldManager->isWorldLoaded(name: $worldName)) {
					$worldManager->unloadWorld(world: $worldManager->getWorldByName($worldName));
				}

				$this->getAsyncPool()->submitTask(task: new CreateZipAsyncTask(
					directoryFullPath: Path::join($this->getServerWorldsPath(), $worldName),
					zipFileFullPath: $zipFileFullPath
				));

				$worldManager->loadWorld(name: $worldName);

				if (!is_null($onSuccess)) $onSuccess($data);
			}
		);
	}

	/**
	 * @param string $arenaID
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @param bool $alertConsole
	 * @return void
	 */
	public function removeArena(string $arenaID, ?Closure $onSuccess = null, ?Closure $onFail = null, bool $alertConsole = true): void
	{
		$this->arenaExistsInDatabase(
			arenaID: $arenaID,
			resultClosure: function ($arenaExists) use ($arenaID, $onSuccess, $onFail, $alertConsole): void {
				if (!$arenaExists) {
					if (!is_null($onFail)) $onFail($arenaID, "Arena does not exists");
					return;
				}

				$arenasManager = $this->getArenasManager();

				self::$database->executeChange(
					queryName: SqlQueries::REMOVE_ARENA,
					args: ["arenaID" => $arenaID],
					onSuccess: function ($rows) use ($arenasManager, $arenaID, $onSuccess, $alertConsole): void {
						if ($arenasManager->hasLoadedArena(arenaID: $arenaID)) {
							$arenasManager->unsignFromBeingLoaded(
								arenaID: $arenaID,
								onSuccess: fn () => !is_null($onSuccess) ? $onSuccess($arenaID) : null
							);
						}

						if ($alertConsole) self::$plugin->getLogger()->alert(message: "Arena: $arenaID has been successfully removed");
					}
				);
			}
		);
	}

	/**
	 * @param string $arenaID
	 * @param Closure $resultClosure
	 * @return void
	 */
	public function arenaExistsInDatabase(string $arenaID, Closure $resultClosure): void
	{
		self::$database->executeSelect(
			queryName: SqlQueries::GET_ALL_ARENAS,
			args: [],
			onSelect: function ($rows) use ($resultClosure, $arenaID): void {
				foreach ($rows as $arenasData) {
					if (strtolower($arenasData["arenaID"]) === strtolower($arenaID)) {
						$resultClosure(true);
						return;
					}
				}
				$resultClosure(false);
			}
		);
	}

	/**
	 * @param Player $player
	 * @param string $arenaID
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function addPlayerToSetupArena(Player $player, string $arenaID, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$this->arenaExistsInDatabase(
			arenaID: $arenaID,
			resultClosure: function ($arenaExists) use ($player, $arenaID, $onSuccess, $onFail): void {
				if (!$arenaExists) {
					if (!is_null($onFail)) $onFail($arenaID, "arena doesnt exists in db");
					return;
				}

				if ($this->getArenasManager()->hasLoadedArena($arenaID)) {
					if (!is_null($onFail)) $onFail($arenaID, "unable to add player to setup a loaded arena");
					return;
				}

				$this->getSetupManager()->add(
					player: $player,
					arenaID: $arenaID,
					onSuccess: fn (SetupPlayer $player) => !is_null($onSuccess) ? $onSuccess($player) : null,
					onFail: fn ()  => !is_null($onFail) ? $onFail($arenaID, "You are already inside the setup") : null
				);
			}
		);
	}

	/**
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function finishArenaSetup(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$setupManager = $this->getSetupManager();
		if (!$setupManager->has(bytes: $player->getUniqueId()->getBytes())) {
			if (!is_null($onFail)) $onFail("The player is not inside the setup");
			return;
		}

		$setupManager->get(
			bytes: $player->getUniqueId()->getBytes(),
			onSuccess: function (SetupPlayer $setupPlayer) use ($setupManager, $onSuccess, $onFail): void {
				$setupSettingsQueue = $setupPlayer->getSetupSettingsQueue();
				$arenaID = $setupPlayer->getSetupingArenaID();

				$fail = fn (SqlError $error) => !is_null($onFail) ? $onFail($error->getMessage()) : null;

				self::$database->executeChange(
					queryName: SqlQueries::UPDATE_ARENA_SPAWNS,
					args: [
						"arenaID" => $arenaID,
						"spawns" => json_encode($setupSettingsQueue->getSpawns())
					],
					onSuccess: null,
					onError: $fail
				);
				self::$database->executeChange(
					queryName: SqlQueries::UPDATE_ARENA_LOBBY_SETTINGS,
					args: [
						"arenaID" => $arenaID,
						"settings" => $setupSettingsQueue->getLobbySettings()
					],
					onSuccess: null,
					onError: $fail
				);
				self::$database->executeChange(
					queryName: SqlQueries::UPDATE_ARENA_DATA,
					args: [
						"arenaID" => $arenaID,
						"arenaData" => $setupSettingsQueue->getArenaData()
					],
					onSuccess: null,
					onError: $fail
				);

				if ($setupSettingsQueue->hasExtraData()) {
					self::$database->executeChange(
						queryName: SqlQueries::UPDATE_ARENA_EXTRA_DATA,
						args: [
							"arenaID" => $arenaID,
							"extraData" => $setupSettingsQueue->getExtraData()
						],
						onSuccess: null,
						onError: $fail
					);
				}

				$setupSettingsQueue->clear();

				$this->loadArena(
					arenaID: $arenaID,
					onSuccess: fn (Arena $arena) => !is_null($onSuccess) ? $onSuccess($arena) : null,
					onFail: fn () => !is_null($onFail) ? $onFail("unable to load arena") : null
				);

				$setupManager->remove(player: $setupPlayer->getCells());
			}
		);
	}

	/**
	 * @param Player $player
	 * @return void
	 */
	public function isPlayerInsideAnArena(Player $player): bool
	{
		$arenasManager = $this->getArenasManager();
		$allArenas = $arenasManager->getAll();

		return !empty(array_filter($allArenas, static function(Arena $arena) {
			return array_key_exists($player->getUniqueId()->getBytes(), $arena->getMode()->getPlayers());
		}));
	}

	/**
	 * @param Player $player
	 * @param Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function getPlayerArena(Player $player, Closure $onSuccess, ?Closure $onFail = null): void
	{
		$arenasManager = $this->getArenasManager();
		$allArenas = $arenasManager->getAll();

		$possibleArenas = array_filter($allArenas, static function ($arena) use ($player) {
			return array_key_exists($player->getUniqueId()->getBytes(), $arena->getMode()->getPlayers());
		});

		if (empty($possibleArenas)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$arena = array_shift($possibleArenas);
		if (is_null($arena)) {
			if (!is_null($onFail)) $onFail();
			return;
		}
		$onSuccess($arena);
	}

	/**
	 * @param Player $player
	 * @param string $arenaID
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function joinArena(Player $player, string $arenaID, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$this->getArenasManager()->getLoadedArena(
			arenaID: $arenaID,
			onSuccess: static function (Arena $arena) use ($player, $onSuccess, $onFail): void {
				$arena->join(
					player: $player,
					onSuccess: static function () use ($onSuccess, $arena): void {
						if (!is_null($onSuccess)) $onSuccess($arena);
					},
					onFail: $onFail
				);
			},
			onFail: static function ($arenaID) use ($onFail): void {
				if (!is_null($onFail)) $onFail("Arena not found");
			}
		);
	}

	/**
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function joinRandomArena(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$arenasManager = $this->getArenasManager();
		$allArenas = $arenasManager->getAll();
		$arenaMessages = $this->getArenaMessagesClass();

		if (empty($allArenas)) {
			if (!is_null($onFail)) $onFail($arenaMessages->NoArenasFound());
			return;
		}

		$sortedArenas = [];
		foreach ($arenasManager->getAll() as $arenaID => $arena) {
			$mode = $arena->getMode();

			if (array_key_exists($player->getUniqueId()->getBytes(), $mode->getPlayers())) {
				if (!is_null($onFail)) $onFail($arenaMessages->PlayerAlreadyInsideAnArena());
				return;
			}

			$sortedArenas[$mode->getPlayerCount()] = $arena;
		}
		ksort($sortedArenas);

		$closedArenas = array_filter($sortedArenas, static function (Arena $value) {
			return (!$value->getState()->equals(ArenaStates::WAITING()) || !$value->getState()->equals(ArenaStates::COUNTDOWN())) && $value->getMode()->getPlayerCount() >= $value->getMode()->getMaxPlayers();
		});
		$openedArenas = array_filter($sortedArenas, static function (Arena $value) {
			return ($value->getState()->equals(ArenaStates::WAITING()) || $value->getState()->equals(ArenaStates::COUNTDOWN())) && $value->getMode()->getPlayerCount() < $value->getMode()->getMaxPlayers();
		});

		$lastKey = array_key_last($openedArenas);
		if (empty($openedArenas) || is_null($lastKey)) {
			if (!is_null($onFail)) $onFail($arenaMessages->NoAvailableArenasFound());
			return;
		}
		$plannedArena = $openedArenas[$lastKey];

		if (in_array($plannedArena, $closedArenas, true)) {
			shuffle($openedArenas);
			$plannedArena = $openedArenas[array_rand($openedArenas)];
		}

		if (count($openedArenas) >= 2) {
			foreach ($openedArenas as $key => $value) {
				$plannedArenaMode = $plannedArena->getMode();
				$valueMode = $value->getMode();

				if ($plannedArenaMode->getPlayerCount() < $valueMode->getMaxPlayers()) {
					$plannedArena = $value;
				} else if ($plannedArenaMode->getPlayerCount() === $valueMode->getMaxPlayers()) {
					$plannedArena = $openedArenas[array_rand($openedArenas)];
				} else if ($plannedArenaMode->getPlayerCount() === 0 && $valueMode->getMaxPlayers() === 0) {
					shuffle($openedArenas);
					$plannedArena = $openedArenas[array_rand($openedArenas)];
				}
			}
		}

		$plannedArena->join(
			player: $player,
			onSuccess: fn () => !is_null($onSuccess) ? $onSuccess($plannedArena) : null,
			onFail: $onFail
		);
	}

	/**
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @param bool $notifyPlayers
	 * @param bool $force
	 * @return void
	 */
	public function leaveArena(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null, bool $notifyPlayers = true, bool $force = false): void
	{
		$this->getPlayerArena(
			player: $player,
			onSuccess: fn (Arena $arena) => $arena->quit(
				player: $player,
				onSuccess: fn () => !is_null($onSuccess) ? $onSuccess($arena->getID()) : null,
				onFail: $onFail,
				notifyPlayers: $notifyPlayers,
				force: $force
			),
			onFail: fn () => !is_null($onFail) ? $onFail($this->getArenaMessagesClass()->NotInsideAnArenaToLeave()) : null
		);
	}
}
