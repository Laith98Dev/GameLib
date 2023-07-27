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

namespace vp817\GameLib\utils;

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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionProperty;
use Symfony\Component\Filesystem\Path;
use ZipArchive;
use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_values;
use function basename;
use function count;
use function dirname;
use function extension_loaded;
use function fclose;
use function file_exists;
use function fopen;
use function implode;
use function is_array;
use function is_bool;
use function is_dir;
use function is_file;
use function is_string;
use function mkdir;
use function realpath;
use function rmdir;
use function rtrim;
use function scandir;
use function str_replace;
use function stream_copy_to_stream;
use function strlen;
use function substr;
use function trim;
use function unlink;
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
		return empty(array_diff_key(array_flip($keys), $array));
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function removeLastSlashFromPath(string $path): string
	{
		return rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $path), "/");
	}

	/**
	 * @param string $path
	 * @param string $filename
	 * @return resource|null
	 * @throws AssumptionFailedError
	 */
	private static function getResource(string $path, string $filename)
	{
		$filename = self::removeLastSlashFromPath(path: $filename);
		if (file_exists($path . $filename)) {
			$resource = fopen($path . $filename, "rb");
			if ($resource === false) throw new AssumptionFailedError(message: "fopen() should not fail on a file which exists");
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

		if (($resource = self::getResource(
			path: $resourcePath,
			filename: $filename
		)) === null) {
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
		if ($fp === false) throw new AssumptionFailedError(message: "fopen() should not fail with wb flags");

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
		$property = new ReflectionProperty(
			class: PluginBase::class,
			property: "resourceProvider"
		);
		/** @var ResourceProvider $resourceProvider */
		$resourceProvider = $property->getValue(object: $plugin);
		$property2 = new ReflectionProperty(
			class: $resourceProvider,
			property: "file"
		);
		$resourcePath = $property2->getValue(object: $resourceProvider);
		$resourcePathNoSl = self::removeLastSlashFromPath(path: $resourcePath);
		if (!is_dir($resourcePathNoSl)) {
			@mkdir($resourcePathNoSl);
		}
		return self::saveResourceToPlugin(
			plugin: $plugin,
			resourcePath: $fromPath,
			filename: $filename,
			pathToUploadTo: $resourcePath
		);
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
			throw new ConfigException(message: "Database settings are missing or incorrect");
		}

		$type = (string) $configData["type"];
		if ($type === "") {
			throw new ConfigException(message: "Database type is missing");
		}

		if (count($sqlMap) === 0) {
			throw new InvalidArgumentException(message: "Parameter $sqlMap cannot be empty");
		}

		$pdo = ($configData["prefer-pdo"] ?? false) && extension_loaded("pdo");

		$dialect = null;
		$placeHolder = null;
		switch (strtolower($type)) {
			case "sqlite":
			case "sqlite3":
			case "sq3":
				if (!$pdo && !extension_loaded("sqlite3")) {
					throw new ExtensionMissingException(extensionName: "sqlite3");
				}

				$fileName = self::resolvePath(
					folder: $plugin->getDataFolder(),
					path: $configData["sqlite"]["file"] ?? "data.sqlite"
				);
				if ($pdo) {
					// TODO add PDO support
				} else {
					$factory = Sqlite3Thread::createFactory(path: $fileName);
				}
				$dialect = "sqlite";
				break;
			case "mysql":
			case "mysqli":
				if (!$pdo && !extension_loaded("mysqli")) {
					throw new ExtensionMissingException(extensionName: "mysqli");
				}

				if (!isset($configData["mysql"])) {
					throw new ConfigException(message: "Missing MySQL settings");
				}

				$cred = MysqlCredentials::fromArray(
					array: $configData["mysql"],
					defaultSchema: strtolower($plugin->getName())
				);

				if ($pdo) {
					// TODO add PDO support
				} else {
					$factory = MysqliThread::createFactory(
						credentials: $cred,
						logger: $plugin->getServer()->getLogger()
					);
					$placeHolder = "?";
				}
				$dialect = "mysql";
				break;
		}

		if (!isset($dialect, $factory, $sqlMap[$dialect])) {
			throw new ConfigException(message: "Unsupported database type \"$type\". Try \"" . implode("\" or \"", array_keys($sqlMap)) . "\".");
		}

		$pool = new SqlThreadPool(
			workerFactory: $factory,
			workerLimit: $configData["worker-limit"] ?? 1
		);
		while (!$pool->connCreated()) {
			usleep(1000);
		}
		if ($pool->hasConnError()) {
			throw new SqlError(
				stage: SqlError::STAGE_CONNECT,
				errorMessage: $pool->getConnError()
			);
		}

		$connector = new DataConnectorImpl(
			plugin: $plugin,
			thread: $pool,
			placeHolder: $placeHolder,
			logQueries: $logQueries ?? !libasynql::isPackaged()
		);
		foreach (is_string($sqlMap[$dialect]) ? [$sqlMap[$dialect]] : $sqlMap[$dialect] as $filePath) {
			$realPath = realpath($filePath);
			$pathFilename = basename($realPath);
			$resource = self::getResource(
				path: str_replace($pathFilename, "", $realPath),
				filename: $pathFilename
			);
			if ($resource === null) {
				throw new InvalidArgumentException(message: "$realPath does not exist");
			}
			$connector->loadQueryFile(fh: $resource);
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
	 * @param array $replacement
	 * @param string $message
	 * @return string
	 */
	public static function replaceMessageContent(array $replacement, string $message): string
	{
		return str_replace(array_keys($replacement), array_values($replacement), $message);
	}

	/**
	 * @param WorldManager $manager
	 * @param string $worldName
	 * @param null|World &$world
	 * @return void
	 */
	public static function lazyUpdateWorld(WorldManager $worldManager, string $worldName, ?World &$world): void
	{
		$fn = static function () use ($worldManager, $worldName): ?World {
			if (!$worldManager->isWorldLoaded(name: $worldName)) $worldManager->loadWorld(name: $worldName);

			return $worldManager->getWorldByName(name: $worldName);
		};

		$world = $fn();
	}

	/**
	 * @param string $directoryFullPath
	 * @return bool
	 */
	public static function deleteDirectory(string $directoryFullPath): bool
	{
		if (!file_exists($directoryFullPath)) {
			return false;
		}

		if (!is_dir($directoryFullPath)) {
			return unlink($directoryFullPath);;
		}

		foreach (scandir($directoryFullPath) as $item) {
			if ($item == "." || $item == "..") {
				continue;
			}

			if (!self::deleteDirectory(Path::join($directoryFullPath, $item))) {
				return true;
			}
		}

		return rmdir($directoryFullPath);
	}

	/**
	 * @param string $directoryFullPath
	 * @param string $zipFileFullPath
	 * @return bool
	 */
	public static function zipDirectory(string $directoryFullPath, string $zipFileFullPath): bool
	{
		if (!file_exists($directoryFullPath)) {
			return false;
		}
		if (!is_dir($directoryFullPath)) {
			return false;
		}

		if (is_file($zipFileFullPath)) {
			unlink($zipFileFullPath);
		}

		$directoryFullPath = realpath($directoryFullPath);

		$recursiveIterator = new RecursiveIteratorIterator(
			iterator: new RecursiveDirectoryIterator($directoryFullPath),
			mode: RecursiveIteratorIterator::LEAVES_ONLY
		);

		$zip = new ZipArchive;
		$zip->open(
			filename: $zipFileFullPath,
			flags: ZipArchive::CREATE | ZipArchive::OVERWRITE
		);

		foreach ($recursiveIterator as $fileInfo) {
			if (!$fileInfo->isDir()) {
				$realPath = $fileInfo->getRealPath();
				if (is_bool($realPath)) {
					continue;
				}
				$filePath = $fileInfo->getPath() . "/" . $fileInfo->getBasename();
				$zip->addFile(
					filepath: $realPath,
					entryname: substr($filePath, strlen($directoryFullPath) + 1)
				);
			}
		}

		$zip->close();

		unset($zip);
		return true;
	}

	/**
	 * @param string $zipFileFullPath
	 * @param string $extractionFullPath
	 * @return bool
	 */
	public static function extractZipFile(string $zipFileFullPath, string $extractionFullPath): bool
	{
		if (!file_exists($zipFileFullPath)) {
			return false;
		}

		if (!is_file($zipFileFullPath)) {
			return false;
		}

		$zip = new ZipArchive;
		$zip->open(filename: $zipFileFullPath);
		$zip->extractTo(pathto: $extractionFullPath);
		$zip->close();

		unset($zip);
		return true;
	}
}
