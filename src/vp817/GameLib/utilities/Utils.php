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

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\ResourceProvider;
use pocketmine\utils\AssumptionFailedError;
use Symfony\Component\Filesystem\Path;

final class Utils
{

	/**
	 * @param array $keys
	 * @param array $array
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
	 * 
	 */
	private static function getResource(string $path, string $filename){
		$filename = rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $filename), "/");
		if(file_exists($path . $filename)){
			$resource = fopen($path . $filename, "rb");
			if($resource === false) throw new AssumptionFailedError("fopen() should not fail on a file which exists");
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
	public static function saveResourceToPlugin(PluginBase $plugin, string $resourcePath, string $filename, string $pathToUploadTo = "", bool $replace = false) : bool{
		if(trim($filename) === ""){
			return false;
		}

		if(($resource = self::getResource($resourcePath, $filename)) === null){
			return false;
		}

		$path = $plugin->getDataFolder();
		if (trim($pathToUploadTo) !== "") {
			$path = $pathToUploadTo;
		} 
		$out = Path::join($path, $filename);
		if(!file_exists(dirname($out))){
			mkdir(dirname($out), 0755, true);
		}

		if(file_exists($out) && !$replace){
			return false;
		}

		$fp = fopen($out, "wb");
		if($fp === false) throw new AssumptionFailedError("fopen() should not fail with wb flags");

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
		$property = new \ReflectionProperty(PluginBase::class, "resourceProvider");
		$property->setAccessible(true);
		/** @var ResourceProvider $resourceProvider */
		$resourceProvider = $property->getValue($plugin);
		$property2 = new \ReflectionProperty($resourceProvider, "file");
		$property2->setAccessible(true);
		$resourcePath = $property2->getValue($resourceProvider);
		$resourcePathNoSl = substr($resourcePath, 0, strlen($resourcePath) - 1);
		if (!is_dir($resourcePathNoSl)) {
			@mkdir($resourcePathNoSl);
		}
		// var_dump($resourcePathNoSl);
		return self::saveResourceToPlugin($plugin, $fromPath, $filename, $resourcePath);
	}
}