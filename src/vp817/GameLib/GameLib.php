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

use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use Symfony\Component\Filesystem\Path;
use vp817\GameLib\arena\Arena;
use vp817\GameLib\arena\ArenaDataParser;
use vp817\GameLib\arena\message\ArenaMessages;
use vp817\GameLib\arena\message\DefaultArenaMessages;
use vp817\GameLib\player\PlayerTeam;
use vp817\GameLib\utilities\SqlQueries;
use vp817\GameLib\utilities\Utils;

final class GameLib
{

	/** @var null|PluginBase $plugin */
	private static ?PluginBase $plugin = null;
	/** @var ?DataConnector $plugin */
	private static ?DataConnector $database = null;
	/** @var ArenaMessages $arenaMessages */
	private ArenaMessages $arenaMessages;
	/** @var string $arenasBackupPath */
	private string $arenasBackupPath;
	/** @var Arena[] $loadedArenas */
	private array $loadedArenas = []; // turn to arenas manager
	/** @var PlayerTeam[] $teams */
	private array $teams = [];

	/**
	 * initialize a new gamelib
	 * 
	 * @param PluginBase $plugin
	 * @param array $database
	 * @return GameLib
	 * @throws \RuntimeException
	 */
	public static function init(PluginBase $plugin, array $database): GameLib
	{
		if (self::$plugin !== null) {
			throw new \RuntimeException("GameLib is already initialized");
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

		// $sqlMapPath = $plugin->getDataFolder() . "sqlMap";
		// if (!is_dir($sqlMapPath)) {
		// 	@mkdir($sqlMapPath);
		// }

		foreach (glob(Path::join($this->getResourcesPath(), "*.sql")) as $resource) {
			$filename = basename($resource);
			// Utils::saveResourceToPlugin($plugin, $this->getResourcesPath() . DIRECTORY_SEPARATOR, $filename, $sqlMapPath);
			Utils::saveResourceToPluginResources($plugin, $this->getResourcesPath() . DIRECTORY_SEPARATOR, $filename);
		}

		// --		:arenaID string
		// --		:worldName string
		// --		:waitingLobbyWorldName string
		// --		:mode string
		// --		:maxPlayersPerTeam int
		self::$database = libasynql::create($plugin, $database,
		[
			"sqlite" => "sqlite.sql",
			"mysql" => "mysql.sql"
		]);
		self::$database->executeGeneric(SqlQueries::INIT, [], null, static function (SqlError $error) use ($plugin): void {
			$plugin->getLogger()->error($error->getMessage());
		});
		self::$database->waitAll();
		// self::$database->executeInsert(SqlQueries::ADD_ARENA, [
		// 	"arenaID" => "arenaid1",
		// 	"worldName" => "test1",
		// 	"waitingLobbyWorldName" => "test2",
		// 	"mode" => "solo",
		// 	"maxPlayersPerTeam" => 1
		// ]);

		// self::$database->executeChange(SqlQueries::SET_ARENA_SPAWNS, [
		// 	"arenaID" => "arenaid1",
		// 	"spawns" => json_encode([
		// 		"spawn1" => ["x" => 10, "y" => 15, "z", 13],
		// 		"spawn2" => ["x" => 16, "y" => 15, "z", 14]
		// 		])
		// 	]
		// );

		// self::$database->executeSelect(SqlQueries::GET_ARENA_DATA, ["arenaID" => "arenaid1"], function ($rows) {
		// 	if (!isset($rows[0])) {
		// 		return;
		// 	}
		// 	var_dump($rows[0]);
		// });
		$this->createArena("arena1", "arenaworld", "solo", 1);

		$this->arenaMessages = new DefaultArenaMessages();
	}

	/**
	 * @return string
	 */
	public function getResourcesPath(): string
	{
		return __DIR__ . "/../../../resources";
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
	 * @return void
	 */
	public function loadArenas(): void
	{
		// foreach (glob(Path::join($this->arenasPath . "*", "*.json")) as $arenaFilePath) {
		// 	$parser = new ArenaDataParser(json_decode(Filesystem::fileGetContents($arenaFilePath))); // temp
		// 	$arenaID = $parser->parse("arenaID");
		// 	if (array_key_exists($arenaID, $this->loadedArenas)) {
		// 		continue;
		// 	}
		// 	$this->loadedArenas[$arenaID] = new Arena($this, $parser, true);
		// }
	}

	/**
	 * @param string $arenaID
	 * @param string $worldName
	 * @param string $mode
	 * @return void
	 */
	public function createArena(string $arenaID, string $worldName, string $mode, int $maxPlayersPerTeam): void
	{
		if (array_key_exists($arenaID, $this->loadedArenas)) {
			throw new \RuntimeException("Arena is already registered");
		}
		//todo
		// $arenasManager = $this->arenasManager;
		// $this->arenaExistsInDB($arenaID, $worldName, function (bool $arenaExists) use ($arenaID, $worldName, $mode, $maxPlayersPerTeam, &$loadedArenas, $this_): void {
		// 	if ($arenaExists) {
		// 		return;
		// 	}

		// 	$data = [
		// 		"arenaID" => $arenaID,
		// 		"worldName" => $worldName,
		// 		"mode" => $mode,
		// 		"maxPlayersPerTeam" => $maxPlayersPerTeam,
		// 		"spawns" => []
		// 	];

		// 	$loadedArenas[$arenaID] = new Arena($this_, new ArenaDataParser($data));
		// });
		// $arenaDirPath = Path::join($this->arenasPath . $arenaID);
		// if (!is_dir($arenaDirPath)) {
		// 	@mkdir($arenaDirPath);
		// }
		// $arenaFilePath = Path::join($arenaDirPath, $arenaID . ".json"); // temp
		// $file = fopen($arenaFilePath, "w+");
		// fwrite($file, json_encode($data, JSON_PRETTY_PRINT));
		// fclose($file);
		// archive

		// $this->loadedArenas[$arenaID] = new Arena($this, new ArenaDataParser($data));
	}

	public function removeArena(string $arenaID, bool $alertConsole = true): void
	{
		if (!array_key_exists($arenaID, $this->loadedArenas)) {
			throw new \RuntimeException("Arena is already registered");
		}
		// $arenaDirPath = Path::join($this->arenasPath . $arenaID);
		// $arenaFilePath = Path::join($arenaDirPath, $arenaID . ".json");
		// $absBackupPath = $arenaID . DIRECTORY_SEPARATOR . $arenaID . ".zip";
		// $backupFilePath = $this->arenasBackupPath;
		// if (str_contains($backupFilePath, "%arena%")) {
		// 	$backupFilePath = str_replace("%arena%", $absBackupPath, $backupFilePath);
		// } else {
		// 	$backupFilePath .= $absBackupPath;
		// }
		// unlink($backupFilePath);
		// unlink($arenaFilePath);
		// rmdir($arenaDirPath);
		if ($alertConsole) {
			self::$plugin->getLogger()->alert("Arena: $arenaID has been successfully removed");
		}
	}

	/**
	 * @param string $arenaID
	 * @return void
	 */
	public function arenaExistsInDB(string $arenaID, string $worldName, callable $valueCallback): void
	{
		// BUtils::validateCallableSignature($valueCallback, function (bool $exists): void {});

		self::$database->executeSelect(SqlQueries::GET_ALL_ARENAS, [], function ($rows) use ($valueCallback, $arenaID, $worldName): void {
			if (count($rows) > 0) {
				foreach ($rows as $arenas) {
					if ($arenas["arenaID"] === $arenaID) {
						$valueCallback(true);
					} else if ($arenaID["arenaID"] !== $arenaID && $arenas["worldName"] === $worldName) {
						$valueCallback(true);
					}
				}	
			}
			$valueCallback(false);
		});
	}
}
