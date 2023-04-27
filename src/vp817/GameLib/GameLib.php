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
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\ArenaDataParser;
use vp817\GameLib\arena\message\ArenaMessages;
use vp817\GameLib\arena\message\DefaultArenaMessages;
use vp817\GameLib\arena\modes\ArenaModes;
use vp817\GameLib\managers\ArenasManager;
use vp817\GameLib\managers\SetupManager;
use vp817\GameLib\player\PlayerTeam;
use vp817\GameLib\utilities\SqlQueries;
use vp817\GameLib\utilities\Utils;
use function is_dir;
use function mkdir;
use function count;
use function is_null;
use function basename;
use function strtolower;
use function json_encode;

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
	/** @var PlayerTeam[] $teams */
	private array $teams = [];
	/** @var SetupManager $setupManager */
	private SetupManager $setupManager;

	/**
	 * initialize a new gamelib
	 * 
	 * @param PluginBase $plugin
	 * @param array $database
	 * @return GameLib
	 * @throws RuntimeException
	 */
	public static function init(PluginBase $plugin, array $database): GameLib
	{
		if (self::$plugin !== null) {
			throw new RuntimeException("GameLib is already initialized");
		}
		return new GameLib($plugin, $database);
	}

	public static function uninit(): void
	{
		if (isset(self::$database)) {
			self::$database->close();
		}
	}

	/**
	 * @param PluginBase $plugin
	 */
	public function __construct(PluginBase $plugin, array $database = [])
	{
		self::$plugin = $plugin;

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
		return __DIR__ . "/../../../resources/";
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
	 * @param array $teams
	 * @return void
	 */
	public function setTeams(array $teams): void
	{
		$this->teams = $teams;
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
	 * @return array
	 */
	public function getTeams(): array
	{
		return $this->teams;
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
				return;
			}
			foreach ($rows as $arenasData) {
				$arenaID = $arenasData["arenaID"];
				$this->arenaExistsInDB($arenaID, function (bool $arenaExists) use ($arenaID, $arenasData, $onSuccess, $onFail): void {
					if (!$arenaExists || $this->getArenasManager()->has($arenaID)) {
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
	 * @param string $waitingLobbyWorldName
	 * @param string $mode
	 * @param null|int $maxPlayersPerTeam
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 * @throws RuntimeException
	 */
	public function createArena(string $arenaID, string $worldName, string $waitingLobbyWorldName, string $mode, ?int $maxPlayersPerTeam = null, ?callable $onSuccess = null, ?callable $onFail = null): void
	{
		if ($this->getArenasManager()->has($arenaID)) {
			$reason = "Arena is already loaded";
			if (!is_null($onFail)) {
				$onFail($arenaID, $reason);
				return;
			}
			throw new RuntimeException($reason);
		}

		$mode = strtolower($mode);
		if (is_null($maxPlayersPerTeam)) {
			$arenaMode = ArenaModes::fromString($mode);
			if (!is_null($arenaMode)) {
				$maxPlayersPerTeam = $arenaMode->getMaxPlayersPerTeam();
			} else {
				throw new RuntimeException("You need to set the arena max players per team if you are using custom arena modes");
			}
		}

		$this->arenaExistsInDB($arenaID, function (bool $arenaExists) use ($arenaID, $worldName, $waitingLobbyWorldName, $mode, $maxPlayersPerTeam, $onSuccess, $onFail): void {
			if ($arenaExists) {
				if (!is_null($onFail)) {
					$onFail($arenaID, "Arena already exists");
				}
				return;
			}

			$data = [
				"arenaID" => $arenaID,
				"worldName" => $worldName,
				"waitingLobbyWorldName" => $waitingLobbyWorldName,
				"mode" => $mode,
				"maxPlayersPerTeam" => $maxPlayersPerTeam
			];

			self::$database->executeInsert(SqlQueries::ADD_ARENA, $data);

			$data["spawns"] = json_encode([]);

			$this->getArenasManager()->signAsLoaded($arenaID, new Arena($this, new ArenaDataParser($data)), function ($arenaID, $arena) use ($onSuccess): void {
				if (!is_null($onSuccess)) {
					$onSuccess($arenaID, $arena);
				}
			});
		});
	}

	/**
	 * @param string $arenaID
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @param bool $alertConsole
	 * @return void
	 */
	public function removeArena(string $arenaID, ?callable $onSuccess = null, ?callable $onFail = null, bool $alertConsole = true): void
	{
		$this->arenaExistsInDB($arenaID, function($arenaExists) use ($arenaID, $onSuccess, $onFail, $alertConsole): void {
			if (!$arenaExists && !$this->getArenasManager()->has($arenaID)) {
				$reason = "Arena doesnt exists";
				if (!is_null($onFail)) {
					$onFail($arenaID, $reason);
				}
				return;
			}

			self::$database->executeChange(SqlQueries::REMOVE_ARENA, ["arenaID" => $arenaID], function ($rows) use ($arenaID, $onSuccess, $alertConsole): void {
				$this->getArenasManager()->unsignFromBeingLoaded($arenaID, function ($iArenaID) use ($onSuccess): void {
					if (!is_null($onSuccess)) {
						$onSuccess($iArenaID);
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
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function addPlayerToSetupArena(Player $player, string $arenaID, ?callable $onSuccess = null, ?callable $onFail = null): void
	{
		if (!$this->getArenasManager()->has($arenaID)) {
			if (!is_null($onFail)) {
				$onFail($arenaID, "Arena does not exists");
			}
			return;
		}

		$this->getSetupManager()->addToSetupPlayers($player, function() use ($onSuccess): void {
			if (!is_null($onSuccess)) {
				$onSuccess();
			}
		}, function() use ($arenaID, $onFail): void {
			if (!is_null($onFail)) {
				$onFail($arenaID, "You are already inside the setup");
			}
		});
	}

	// /**
	//  * @param Player $player
	//  * @param Closure $onSuccess
	//  * @param Closure $onFail
	//  * @return void
	//  */
	// public function removePlayerFromArenaSetupping(Player $player, ?callable $onSuccess = null, ?callable $onFail = null): void
	// {
	// 	$this->getSetupManager()->removeFromSetupPlayers($player, $onSuccess, $onFail);
	// }
}
