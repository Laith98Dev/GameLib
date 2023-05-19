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

namespace vp817\GameLib\utilities;

use InvalidArgumentException;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\ResourceProvider;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils as PMUtils;
use pocketmine\world\World;
use pocketmine\world\WorldManager;
use poggit\libasynql\base\DataConnectorImpl;
use poggit\libasynql\base\SqlThreadPool;
use poggit\libasynql\ConfigException;
use poggit\libasynql\DataConnector;
use poggit\libasynql\ExtensionMissingException;
use poggit\libasynql\libasynql;
use poggit\libasynql\mysqli\MysqlCredentials;
use poggit\libasynql\mysqli\MysqliThread;
use poggit\libasynql\SqlError;
use poggit\libasynql\sqlite3\Sqlite3Thread;
use ReflectionProperty;
use Symfony\Component\Filesystem\Path;
use function array_key_exists;
use function array_keys;
use function basename;
use function count;
use function dirname;
use function extension_loaded;
use function fclose;
use function file_exists;
use function fopen;
use function is_array;
use function is_dir;
use function implode;
use function is_string;
use function realpath;
use function rtrim;
use function stream_copy_to_stream;
use function str_replace;
use function mkdir;
use function strlen;
use function substr;
use function trim;
use function usleep;

final class Utils
{

	/**
	 * @param array $keys
	 * @param array $array
	 * @return bool
	 */
	public static function arrayKeysExist(array $keys, array $array): bool
	{
		foreach ($keys as $key) {
			if (array_key_exists($key, $array)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $path
	 * @param string $filename
	 * @return resource|null
	 * @throws AssumptionFailedError
	 */
	private static function getResource(string $path, string $filename)
	{
		$filename = rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $filename), "/");
		if (file_exists($path . $filename)) {
			$resource = fopen($path . $filename, "rb");
			if ($resource === false) throw new AssumptionFailedError("fopen() should not fail on a file which exists");
			return $resource;
		}

		return null;
	}

	/**
	 * @param PluginBase $plugin
	 * @param string $resourcePath
	 * @param string $filename
	 * @param string $pathToUploadTo
	 * @param bool $replace
	 */
	public static function saveResourceToPlugin(PluginBase $plugin, string $resourcePath, string $filename, string $pathToUploadTo = "", bool $replace = false): bool
	{
		if (trim($filename) === "") {
			return false;
		}

		if (($resource = self::getResource($resourcePath, $filename)) === null) {
			return false;
		}

		$path = $plugin->getDataFolder();
		if (trim($pathToUploadTo) !== "") {
			$path = $pathToUploadTo;
		}
		$out = Path::join($path, $filename);
		if (!file_exists(dirname($out))) {
			mkdir(dirname($out), 0755, true);
		}

		if (file_exists($out) && !$replace) {
			return false;
		}

		$fp = fopen($out, "wb");
		if ($fp === false) throw new AssumptionFailedError("fopen() should not fail with wb flags");

		$ret = stream_copy_to_stream($resource, $fp) > 0;
		fclose($fp);
		fclose($resource);
		return $ret;
	}

	/**
	 * @param PluginBase $plugin
	 * @param string $fromPath
	 * @param string $filename
	 */
	public static function saveResourceToPluginResources(PluginBase $plugin, string $fromPath, string $filename): bool
	{
		$property = new ReflectionProperty(PluginBase::class, "resourceProvider");
		$property->setAccessible(true);
		/** @var ResourceProvider $resourceProvider */
		$resourceProvider = $property->getValue($plugin);
		$property2 = new ReflectionProperty($resourceProvider, "file");
		$property2->setAccessible(true);
		$resourcePath = $property2->getValue($resourceProvider);
		$resourcePathNoSl = substr($resourcePath, 0, strlen($resourcePath) - 1);
		if (!is_dir($resourcePathNoSl)) {
			@mkdir($resourcePathNoSl);
		}
		return self::saveResourceToPlugin($plugin, $fromPath, $filename, $resourcePath);
	}

	/**
	 * libasynql::create modified
	 * 
	 * @param PluginBase $plugin
	 * @param mixed $configData
	 * @param string[]|string[][] $sqlMap
	 * @param bool $logQueries
	 * @return DataConnector
	 * @throws SqlError
	 */
	public static function libasynqlCreateForVirion(PluginBase $plugin, $configData, array $sqlMap, bool $logQueries = null): DataConnector
	{
		libasynql::detectPackaged();

		if (!is_array($configData)) {
			throw new ConfigException("Database settings are missing or incorrect");
		}

		$type = (string) $configData["type"];
		if ($type === "") {
			throw new ConfigException("Database type is missing");
		}

		if (count($sqlMap) === 0) {
			throw new InvalidArgumentException("Parameter $sqlMap cannot be empty");
		}

		$pdo = ($configData["prefer-pdo"] ?? false) && extension_loaded("pdo");

		$dialect = null;
		$placeHolder = null;
		switch (strtolower($type)) {
			case "sqlite":
			case "sqlite3":
			case "sq3":
				if (!$pdo && !extension_loaded("sqlite3")) {
					throw new ExtensionMissingException("sqlite3");
				}

				$fileName = self::resolvePath($plugin->getDataFolder(), $configData["sqlite"]["file"] ?? "data.sqlite");
				if ($pdo) {
					// TODO add PDO support
				} else {
					$factory = Sqlite3Thread::createFactory($fileName);
				}
				$dialect = "sqlite";
				break;
			case "mysql":
			case "mysqli":
				if (!$pdo && !extension_loaded("mysqli")) {
					throw new ExtensionMissingException("mysqli");
				}

				if (!isset($configData["mysql"])) {
					throw new ConfigException("Missing MySQL settings");
				}

				$cred = MysqlCredentials::fromArray($configData["mysql"], strtolower($plugin->getName()));

				if ($pdo) {
					// TODO add PDO support
				} else {
					$factory = MysqliThread::createFactory($cred, $plugin->getServer()->getLogger());
					$placeHolder = "?";
				}
				$dialect = "mysql";

				break;
		}

		if (!isset($dialect, $factory, $sqlMap[$dialect])) {
			throw new ConfigException("Unsupported database type \"$type\". Try \"" . implode("\" or \"", array_keys($sqlMap)) . "\".");
		}

		$pool = new SqlThreadPool($factory, $configData["worker-limit"] ?? 1);
		while (!$pool->connCreated()) {
			usleep(1000);
		}
		if ($pool->hasConnError()) {
			throw new SqlError(SqlError::STAGE_CONNECT, $pool->getConnError());
		}

		$connector = new DataConnectorImpl($plugin, $pool, $placeHolder, $logQueries ?? !libasynql::isPackaged());
		foreach (is_string($sqlMap[$dialect]) ? [$sqlMap[$dialect]] : $sqlMap[$dialect] as $filePath) {
			$realPath = realpath($filePath);
			$pathFilename = basename($realPath);
			$resource = static::getResource(str_replace($pathFilename, "", $realPath), $pathFilename);
			if ($resource === null) {
				throw new InvalidArgumentException("$realPath does not exist");
			}
			$connector->loadQueryFile($resource);
		}

		return $connector;
	}

	/**
	 * @param string $folder
	 * @param string $path
	 * @return string
	 */
	private static function resolvePath(string $folder, string $path): string
	{
		if ($path[0] === "/") {
			return $path;
		}
		if (PMUtils::getOS() === "win") {
			if ($path[0] === "\\" || $path[1] === ":") {
				return $path;
			}
		}
		return $folder . $path;
	}

	/**
	 * @param WorldManager $worldManager
	 * @param string $worldName
	 * @return null|World
	 */
	public static function getWorldByName(WorldManager $worldManager, string $worldName): ?World
	{
		$world = $worldManager->getWorldByName($worldName);
		if ($world === null || !$worldManager->isWorldLoaded($worldName)) {
			$worldManager->loadWorld($worldName);
		}
		return $world;
	}
}
