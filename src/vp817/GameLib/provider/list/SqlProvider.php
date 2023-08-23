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

namespace vp817\GameLib\provider\list;

use Closure;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use Symfony\Component\Filesystem\Path;
use vp817\GameLib\exceptions\GameLibInvalidArgumentException;
use vp817\GameLib\exceptions\GameLibMissingKeysException;
use vp817\GameLib\GameLib;
use vp817\GameLib\provider\Provider;
use vp817\GameLib\utils\SqlQueries;
use vp817\GameLib\utils\Utils;
use const GAMELIB_RESOURCE_PATH;
use function basename;
use function glob;
use function strtolower;

class SqlProvider extends Provider
{

	private ?DataConnector $database = null;

	/**
	 * @param mixed ...$arguments
	 * @return void
	 * @throws GameLibInvalidArgumentException|GameLibMissingKeysException
	 */
	public function init(mixed ...$arguments): void
	{
		$gamelib = $arguments[0];
		$plugin = $arguments[1];

		if (!is_object($gamelib)) {
			throw new GameLibInvalidArgumentException(message: "The gamelib is not an object");
		}

		if (!$gamelib instanceof GameLib) {
			throw new GameLibInvalidArgumentException(message: "The gamelib is invalid");
		}

		if (!is_object($plugin)) {
			throw new GameLibInvalidArgumentException(message: "The plugin is not an object");
		}

		if (!$plugin instanceof PluginBase) {
			throw new GameLibInvalidArgumentException(message: "The plugin is invalid");
		}

		$databaseSettings = $gamelib->getDatabaseSettings();
		$sqlType = $databaseSettings["type"];

		$database = [
			"type" => $sqlType
		];

		if ($sqlType == "sqlite") {
			$database["sqlite"] = [
				"file" => $databaseSettings["dataFileName"] ?? "data.sql"
			];
		} else if ($sqlType == "mysql") {
			if (!Utils::arrayKeysExist(
				[
					"host",
					"username",
					"password",
					"schema"
				],
				$databaseSettings
			)) {
				throw new GameLibMissingKeysException(message: "the sql database is missing some keys for mysql database");
			}
			unset($databaseSettings["type"]);
			$database["mysql"][] = $databaseSettings;
		}

		$sqlMapPath = $plugin->getDataFolder() . "SqlMap";
		if (!is_dir($sqlMapPath)) {
			@mkdir($sqlMapPath);
		}

		foreach (glob(Path::join(GAMELIB_RESOURCE_PATH, "*.sql")) as $resource) {
			$filename = basename($resource);
			Utils::saveResourceToPlugin(
				plugin: $plugin,
				resourcePath: GAMELIB_RESOURCE_PATH,
				filename: $filename,
				pathToUploadTo: $sqlMapPath
			);
		}

		$this->database = Utils::libasynqlCreateForVirion(
			plugin: $plugin,
			configData: $database,
			sqlMap: [
				"sqlite" => Path::join($sqlMapPath, "sqlite.sql"),
				"mysql" => Path::join($sqlMapPath, "mysql.sql")
			]
		);

		$this->database?->executeGeneric(
			queryName: SqlQueries::INIT,
			args: [],
			onSuccess: null,
			onError: static function (SqlError $error) use ($plugin): void {
				$plugin->getLogger()->error($error->getMessage());
			}
		);

		$this->database?->waitAll();
	}

	/**
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function getAllArenas(Closure $onSuccess, Closure $onFail): void
	{
		$this->database?->executeSelect(
			queryName: SqlQueries::GET_ALL_ARENAS,
			args: [],
			onSelect: static function (array $rows) use ($onSuccess, $onFail): void {
				if (empty($rows)) {
					$onFail("None", "No arenas found");
					return;
				}

				$onSuccess($rows);
			},
			onError: static fn (SqlError $error) => $onFail("None", $error->getMessage())
		);
	}

	/**
	 * @param string $arenaID
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function isArenaValid(string $arenaID, Closure $onSuccess, Closure $onFail): void
	{
		$this->database?->executeSelect(
			queryName: SqlQueries::GET_ALL_ARENAS,
			args: [],
			onSelect: static function (array $rows) use ($arenaID, $onSuccess, $onFail): void {
				foreach ($rows as $arenasData) {
					if (strtolower($arenasData["arenaID"]) === strtolower($arenaID)) {
						$onSuccess();
						return;
					}
				}
				$onFail();
			},
			onError: static fn (SqlError $error) => $onFail($error->getMessage())
		);
	}

	/**
	 * @param string $arenaID
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function getArenaDataByID(string $arenaID, Closure $onSuccess, Closure $onFail): void
	{
		$this->database?->executeSelect(
			queryName: SqlQueries::GET_ARENA_DATA,
			args: ["arenaID" => $arenaID],
			onSelect: static function (array $rows) use ($onSuccess, $onFail): void {
				if (empty($rows)) {
					$onFail("None", "Arena not found");
					return;
				}

				$onSuccess($rows);
			},
			onError: static fn (SqlError $error) => $onFail("None", $error->getMessage())
		);
	}

	/**
	 * @param array $data
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function insertArenaData(array $data, Closure $onSuccess, Closure $onFail): void
	{
		$this->database?->executeInsert(
			queryName: SqlQueries::ADD_ARENA,
			args: $data,
			onInserted: $onSuccess,
			onError: static fn (SqlError $error) => $onFail($error->getMessage())
		);
	}

	/**
	 * @param string $arenaID
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function removeArenaDataByID(string $arenaID, Closure $onSuccess, Closure $onFail): void
	{
		$this->database?->executeChange(
			queryName: SqlQueries::REMOVE_ARENA,
			args: ["arenaID" => $arenaID],
			onSuccess: $onSuccess,
			onError: static fn (SqlError $error) => $onFail($error->getMessage())
		);
	}

	/**
	 * @param string $arenaID
	 * @param string $data
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function setArenaSpawnsDataByID(string $arenaID, string $data, ?Closure $onSuccess, ?Closure $onFail): void
	{
		$this->database?->executeChange(
			queryName: SqlQueries::UPDATE_ARENA_SPAWNS,
			args: [
				"arenaID" => $arenaID,
				"spawns" => $data
			],
			onSuccess: $onSuccess,
			onError: static fn (SqlError $error) => $onFail($error->getMessage())
		);
	}

	/**
	 * @param string $arenaID
	 * @param string $data
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function setArenaLobbySettingsDataByID(string $arenaID, string $data, ?Closure $onSuccess, ?Closure $onFail): void
	{
		$this->database?->executeChange(
			queryName: SqlQueries::UPDATE_ARENA_LOBBY_SETTINGS,
			args: [
				"arenaID" => $arenaID,
				"settings" => $data
			],
			onSuccess: $onSuccess,
			onError: static fn (SqlError $error) => $onFail($error->getMessage())
		);
	}

	/**
	 * @param string $arenaID
	 * @param string $data
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function setArenaNonExtraDataByID(string $arenaID, string $data, ?Closure $onSuccess, ?Closure $onFail): void
	{
		$this->database?->executeChange(
			queryName: SqlQueries::UPDATE_ARENA_DATA,
			args: [
				"arenaID" => $arenaID,
				"arenaData" => $data
			],
			onSuccess: $onSuccess,
			onError: static fn (SqlError $error) => $onFail($error->getMessage())
		);
	}

	/**
	 * @param string $arenaID
	 * @param string $data
	 * @param Closure $onSuccess
	 * @param Closure $onFail
	 * @return void
	 */
	public function setArenaExtraDataByID(string $arenaID, string $data, ?Closure $onSuccess, ?Closure $onFail): void
	{
		$this->database?->executeChange(
			queryName: SqlQueries::UPDATE_ARENA_EXTRA_DATA,
			args: [
				"arenaID" => $arenaID,
				"extraData" => $data
			],
			onSuccess: $onSuccess,
			onError: static fn (SqlError $error) => $onFail($error->getMessage())
		);
	}

	/**
	 * @return void
	 */
	public function free(): void
	{
		if (!is_null($this->database)) {
			$this->database?->close();
			unset($this->database);
		}
	}
}
