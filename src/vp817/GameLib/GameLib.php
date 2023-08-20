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
use pocketmine\scheduler\AsyncPool;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use pocketmine\utils\Utils as PMUtils;
use pocketmine\world\WorldManager;
use Symfony\Component\Filesystem\Path;
use TypeError;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\parse\ArenaDataParser;
use vp817\GameLib\arena\message\ArenaMessages;
use vp817\GameLib\arena\message\list\MiniGameMessages;
use vp817\GameLib\arena\message\list\PracticeMessages;
use vp817\GameLib\arena\states\ArenaStates;
use vp817\GameLib\event\listener\DefaultArenaListener;
use vp817\GameLib\event\listener\ServerEventListener;
use vp817\GameLib\exceptions\GameLibAlreadyInitException;
use vp817\GameLib\exceptions\GameLibInvalidArgumentException;
use vp817\GameLib\exceptions\GameLibInvalidListenerException;
use vp817\GameLib\exceptions\GameLibMissingComposerException;
use vp817\GameLib\exceptions\GameLibMissingLibException;
use vp817\GameLib\exceptions\GameLibNotInitException;
use vp817\GameLib\managers\ArenasManager;
use vp817\GameLib\managers\SetupManager;
use vp817\GameLib\player\SetupPlayer;
use vp817\GameLib\provider\Provider;
use vp817\GameLib\provider\Providers;
use vp817\GameLib\tasks\async\CreateZipAsyncTask;
use vp817\GameLib\utils\Utils;
use const GAMELIB_COMPOSER_AUTOLOAD_PATH;
use const JSON_THROW_ON_ERROR;
use function array_filter;
use function array_key_exists;
use function array_rand;
use function array_shift;
use function class_exists;
use function count;
use function file_exists;
use function is_dir;
use function is_null;
use function json_encode;
use function mkdir;
use function shuffle;
use function strlen;
use function trim;
use function uksort;
use function unlink;

final class GameLib
{

	private static ?PluginBase $plugin = null;
	private ?Provider $provider = null;
	private ArenasManager $arenasManager;
	private ArenaMessages $arenaMessages;
	private string $arenaListenerClass;
	private string $arenasBackupPath;
	private SetupManager $setupManager;

	/**
	 * initialize a new gamelib
	 * 
	 * databaseSettings usage example:
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
	 * what was said up only applies when you are using the sql provider (default provider)
	 * 
	 * @param PluginBase $plugin
	 * @param GameLibType $libType
	 * @param array $databaseSettings
	 * @return GameLib
	 * @throws GameLibAlreadyInitException|GameLibMissingLibException|GameLibMissingComposerException
	 */
	public static function init(PluginBase $plugin, GameLibType $libType, array $databaseSettings): GameLib
	{
		if (!is_null(self::$plugin)) {
			throw new GameLibAlreadyInitException(message: "GameLib is already initialized for this plugin");
		}

		if (!class_exists("poggit\libasynql\libasynql")) {
			throw new GameLibMissingLibException(message: "libasyql virion not found. unable to use gamelib");
		}

		if (!file_exists(GAMELIB_COMPOSER_AUTOLOAD_PATH)) {
			throw new GameLibMissingComposerException(message: "Composer autoloader for gamelib not found.");
		}

		require_once "GameLibDefinitions.php";
		require_once GAMELIB_COMPOSER_AUTOLOAD_PATH;

		return new GameLib(
			plugin: $plugin,
			libType: $libType,
			databaseSettings: $databaseSettings
		);
	}

	/**
	 * @return void
	 * @throws GameLibNotInitException
	 */
	public function deinit(): void
	{
		if (is_null(self::$plugin)) {
			throw new GameLibNotInitException(message: "There is no instance to uninitialize for gamelib");
		}

		if (!is_null($this->provider)) {
			$this->provider?->free();
			unset($this->provider);
		}
	}

	/**
	 * @param PluginBase $plugin
	 * @param GameLibType $libType
	 * @param array $databaseSettings
	 */
	private function __construct(
		PluginBase $plugin,
		private GameLibType $libType,
		private array $databaseSettings
	) {
		self::$plugin = $plugin;

		$this->provider = Providers::SQL();
		$this->provider?->init($this, self::$plugin);

		$this->arenasManager = new ArenasManager;

		$this->arenaMessages = match (true){
			$libType->equals(GameLibType::MINIGAME()) => new MiniGameMessages,
			$libType->equals(GameLibType::PRACTICE()) => new PracticeMessages,
			default => throw new GameLibInvalidArgumentException("Invalid GameType provided. Please ensure you are using a valid game type and try again.")
		};
		
		$this->setupManager = new SetupManager;
		$this->arenaListenerClass = DefaultArenaListener::class;

		Server::getInstance()->getPluginManager()->registerEvents(
			listener: new ServerEventListener($this),
			plugin: $plugin
		);
	}

	/**
	 * @return array
	 */
	public function getDatabaseSettings(): array
	{
		return $this->databaseSettings;
	}

	/**
	 * @return Provider|null
	 */
	public function getProvider(): ?Provider
	{
		return $this->provider;
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

		$this->arenasBackupPath = $path;
	}

	/**
	 * @param Provider $provider
	 * @return void
	 */
	public function setProvider(Provider $provider): void
	{
		$this->provider = $provider;
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
	 * @return GameLibType
	 */
	public function getLibType(): GameLibType
	{
		return $this->libType;
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
	 * @throws GameLibInvalidListenerException
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
			throw new GameLibInvalidListenerException(message: "The listener that you provided is not an instance of GameLib/DefaultArenaListener.php");
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
		$provider = $this->getProvider();

		$provider?->getAllArenas(
			resultClosure: function ($rows) use ($provider, $onSuccess, $onFail): void {
				if (count($rows) < 1) {
					if (!is_null($onFail)) $onFail("None", "no arenas to be loaded");
					return;
				}

				foreach ($rows as $arenasData) {
					$arenaID = $arenasData["arenaID"];
					$provider?->isArenaNotInvalid(
						arenaID: $arenaID,
						onSuccess: function () use ($arenaID, $arenasData, $onSuccess, $onFail): void {
							if ($this->getArenasManager()->hasLoadedArena(arenaID: $arenaID)) {
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
								onSuccess: static fn (Arena $arena) => !is_null($onSuccess) ? $onSuccess($arena) : null
							);
						},
						onFail: static fn () => !is_null($onFail) ? $onFail($arenaID, "Arena doesnt exists in db. this shouldnt happen") : null
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
		$provider = $this->getProvider();

		$provider?->isArenaNotInvalid(
			arenaID: $arenaID,
			onSuccess: function () use ($provider, $arenaID, $onSuccess, $onFail): void {
				$provider?->getArenaDataByID(
					arenaID: $arenaID,
					onSuccess: function ($rows) use ($onSuccess): void {
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
									dataParser: new ArenaDataParser(
										data: $arenaData
									)
								),
								onSuccess: static fn (Arena $arena) => !is_null($onSuccess) ? $onSuccess($arena) : null
							);
						}
					},
					onFail: $onFail
				);
			},
			onFail: static fn () => !is_null($onFail) ? $onFail() : null
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
		$provider = $this->getProvider();

		$provider?->isArenaNotInvalid(
			arenaID: $arenaID,
			onSuccess: static fn () => !is_null($onFail) ? $onFail($arenaID, "Arena already exists") : null,
			onFail: function () use ($provider, $arenaID, $worldName, $mode, $countdownTime, $arenaTime, $restartingTime, $onSuccess, $onFail): void {
				$data = [
					"arenaID" => $arenaID,
					"worldName" => $worldName,
					"mode" => $mode,
					"countdownTime" => $countdownTime,
					"arenaTime" => $arenaTime,
					"restartingTime" => $restartingTime
				];

				$provider?->insertArenaData(
					data: $data,
					onSuccess: function (int $insertID, int $affectedRows) use ($arenaID, $worldName, $data, $onSuccess): void {
						$zipFileFullPath = Path::join($this->getArenasBackupPath(), $arenaID) . ".zip";
						$worldManager = $this->getWorldManager();

						if ($worldManager->isWorldLoaded(name: $worldName)) {
							$worldManager->unloadWorld(world: $worldManager->getWorldByName($worldName));
						}

						$directoryFullPath = Path::join($this->getServerWorldsPath(), $worldName);

						// TODO: Temp fix and might be permanent
						// There seems to be an issue for creating zip in another thread
						// I have no idea what is causing this since i have debugged everything related to this
						// This is only tested on windows 11 i have not tested on other versions of windows
						if (PMUtils::getOS() === PMUtils::OS_WINDOWS) {
							Utils::zipDirectory(
								directoryFullPath: $directoryFullPath,
								zipFileFullPath: $zipFileFullPath
							);
						} else {
							$this->getAsyncPool()->submitTask(task: new CreateZipAsyncTask(
								directoryFullPath: $directoryFullPath,
								zipFileFullPath: $zipFileFullPath
							));
						}

						$worldManager->loadWorld(name: $worldName);

						if (!is_null($onSuccess)) $onSuccess($data);
					},
					onFail: static fn (string $reason) => !is_null($onFail) ? $onFail($arenaID, $reason) : null
				);
			}
		);
	}

	/**
	 * @param string $arenaID
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function removeArena(string $arenaID, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$provider = $this->getProvider();

		$provider?->isArenaNotInvalid(
			arenaID: $arenaID,
			onSuccess: function () use ($provider, $arenaID, $onSuccess, $onFail): void {
				$arenasManager = $this->getArenasManager();

				$provider?->removeArenaDataByID(
					arenaID: $arenaID,
					onSuccess: function (int $affectedRows) use ($arenasManager, $arenaID, $onSuccess): void {
						if ($arenasManager->hasLoadedArena(arenaID: $arenaID)) {
							$arenasManager->unsignFromBeingLoaded(
								arenaID: $arenaID,
								onSuccess: fn () => !is_null($onSuccess) ? $onSuccess($arenaID) : null
							);
						}
						$zipFileFullPath = Path::join($this->getArenasBackupPath(), $arenaID) . ".zip";

						if (file_exists($zipFileFullPath)) {
							unlink($zipFileFullPath);
						}
					},
					onFail: static fn (string $reason) => !is_null($onFail) ? $onFail($arenaID, $reason) : null
				);
			},
			onFail: static fn () => !is_null($onFail) ? $onFail($arenaID, "Arena does not exists") : null
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
		$provider = $this->getProvider();

		$provider?->isArenaNotInvalid(
			arenaID: $arenaID,
			onSuccess: function () use ($player, $arenaID, $onSuccess, $onFail): void {
				if ($this->getArenasManager()->hasLoadedArena(arenaID: $arenaID)) {
					if (!is_null($onFail)) $onFail($arenaID, "unable to add player to setup a loaded arena");
					return;
				}

				$this->getSetupManager()->add(
					player: $player,
					arenaID: $arenaID,
					onSuccess: fn (SetupPlayer $player) => !is_null($onSuccess) ? $onSuccess($player) : null,
					onFail: fn ()  => !is_null($onFail) ? $onFail($arenaID, "You are already inside the setup") : null
				);
			},
			onFail: static fn () => !is_null($onFail) ? $onFail($arenaID, "The given arena id does not exist in the data") : null
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
		$provider = $this->getProvider();
		$setupManager = $this->getSetupManager();

		if (!$setupManager->has(bytes: $player->getUniqueId()->getBytes())) {
			if (!is_null($onFail)) $onFail("The player is not inside the setup");
			return;
		}


		$setupManager->get(
			bytes: $player->getUniqueId()->getBytes(),
			onSuccess: function (SetupPlayer $setupPlayer) use ($provider, $setupManager, $onSuccess, $onFail): void {
				$setupSettingsQueue = $setupPlayer->getSetupSettingsQueue();
				$arenaID = $setupPlayer->getSetupingArenaID();

				$provider?->setArenaSpawnsDataByID(
					arenaID: $arenaID,
					data: json_encode($setupSettingsQueue->getSpawns(), JSON_THROW_ON_ERROR),
					onSuccess: null,
					onFail: $onFail
				);
				$provider?->setArenaLobbySettingsDataByID(
					arenaID: $arenaID,
					data: $setupSettingsQueue->getLobbySettings(),
					onSuccess: null,
					onFail: $onFail
				);
				$provider?->setArenaLobbySettingsDataByID(
					arenaID: $arenaID,
					data: $setupSettingsQueue->getArenaData(),
					onSuccess: null,
					onFail: $onFail
				);

				if ($setupSettingsQueue->hasExtraData()) {
					$provider?->setArenaLobbySettingsDataByID(
						arenaID: $arenaID,
						data: $setupSettingsQueue->getExtraData(),
						onSuccess: null,
						onFail: $onFail
					);
				}

				$setupSettingsQueue->clear();

				$this->loadArena(
					arenaID: $arenaID,
					onSuccess: static fn (Arena $arena) => !is_null($onSuccess) ? $onSuccess($arena) : null,
					onFail: static fn () => !is_null($onFail) ? $onFail("unable to load arena") : null
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

		return !empty(array_filter($allArenas, static function (Arena $arena) use ($player): bool {
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

		$matchingArenas = array_filter($allArenas, static function ($arena) use ($player) {
			return array_key_exists($player->getUniqueId()->getBytes(), $arena->getMode()->getPlayers());
		});

		if (empty($matchingArenas)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$arena = array_shift($matchingArenas);
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
			onFail: static function ($_) use ($onFail): void {
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

		$existingArena = null;
		$sortedArenas = [];
		foreach ($allArenas as $arenaID => $arena) {
			$mode = $arena->getMode();
			if (!is_null($mode->getPlayers()[$player->getUniqueId()->toString()] ?? null)) {
				$existingArena = $arena;
				break;
			}
			$sortedArenas[$arenaID] = $arena;
		}

		if (!is_null($existingArena)) {
			if (!is_null($onFail)) $onFail($arenaMessages->PlayerAlreadyInsideAnArena());
			return;
		}

		uksort($sortedArenas, static function ($rhs, $lhs) use ($allArenas): int {
			return count($allArenas[$rhs]->getMode()->getPlayers()) <=> count($allArenas[$lhs]->getMode()->getPlayers());
		});

		$openedArenas = [];
		$closedArenas = [];
		foreach ($sortedArenas as $arena) {
			if (($arena->getState()->equals(ArenaStates::WAITING()) || $arena->getState()->equals(ArenaStates::COUNTDOWN())) && $arena->getMode()->getPlayerCount() < $arena->getMode()->getMaxPlayers()) {
				$openedArenas[] = $arena;
			} else {
				$closedArenas[] = $arena;
			}
		}

		if (empty($openedArenas)) {
			if (!is_null($onFail)) $onFail($arenaMessages->NoAvailableArenasFound());
			return;
		}

		$plannedArena = $openedArenas[array_rand($openedArenas)];

		$shuffePlan = static function () use (&$plannedArena): void {
			shuffle($openedArenas);
			$plannedArena = $openedArenas[array_rand($openedArenas)];
		};

		if (!is_null($closedArenas[$plannedArena->getID()] ?? null)) {
			$shuffePlan();
		}

		foreach ($openedArenas as $arena) {
			$plannedArenaMode = $plannedArena->getMode();
			$valueMode = $arena->getMode();

			if ($arena->getID() === $plannedArena->getID()) {
				continue;
			}

			if ($plannedArenaMode->getPlayerCount() < $valueMode->getMaxPlayers()) {
				$plannedArena = $arena;
			} else if (
				($plannedArenaMode->getPlayerCount() === $valueMode->getMaxPlayers())
				||
				($plannedArenaMode->getPlayerCount() === 0 && $valueMode->getMaxPlayers() === 0)
			) { // here seems to be an error. remove this if im wrong since im only testing with 1 arena
				$shuffePlan();
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
